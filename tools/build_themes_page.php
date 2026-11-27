<?php
/*
 * QScms (Qsiscms) - Multi-language Lightweight Content Management System
 * SPDX-License-Identifier: GPL-3.0-or-later
 *
 * 生成官网「主题模板」展示页 themes.html。
 *
 * 用法（CLI）：
 *   php tools/build_themes_page.php <目标站点目录>
 *
 * 做什么：
 *   1. 只列公开主题（$QS_THEMES 白名单，IP/私货主题一律不出现）；
 *   2. 读每个主题的 theme_label.txt 作分类标签，image.jpg 作预览图；
 *   3. 预览图拷到 目标站点/upload/themes/<slug>.jpg；
 *   4. 用官网文章页的页头页脚原样包住，输出 目标站点/themes.html。
 */

if (PHP_SAPI !== 'cli') { exit('CLI only'); }

/* 输出站点目录：默认就是本脚本所在的官网项目根目录，可用第 1 个参数覆盖 */
$outDir = $argv[1] ?? dirname(__DIR__);
if (!is_dir($outDir)) {
    fwrite(STDERR, "目标站点目录不存在：$outDir\n用法：php tools/build_themes_page.php [目标站点目录] [QScms 程序目录]\n");
    exit(1);
}
$outDir = rtrim(str_replace('\\', '/', realpath($outDir)), '/');

/* QScms 程序目录：不写死路径。优先第 2 个参数 / 环境变量 QS_CMS_ROOT；
   否则自动探测官网项目的同级目录里哪个是 QScms 程序（含 index.php + files/theme）。 */
$QScmsRoot = $argv[2] ?? (getenv('QS_CMS_ROOT') ?: '');
if ($QScmsRoot === '') {
    foreach ((glob(dirname(__DIR__, 2) . '/*', GLOB_ONLYDIR) ?: array()) as $cand) {
        if (is_file($cand . '/index.php') && is_dir($cand . '/files/theme')) { $QScmsRoot = $cand; break; }
    }
}
if ($QScmsRoot === '' || !is_dir($QScmsRoot . '/files/theme')) {
    fwrite(STDERR, "找不到 QScms 程序目录，请用第 2 个参数或环境变量 QS_CMS_ROOT 指定\n");
    exit(1);
}
$QScmsRoot = rtrim(str_replace('\\', '/', realpath($QScmsRoot)), '/');
echo "QScms 程序目录：$QScmsRoot\n输出目录：$outDir\n";

/* 公开主题白名单：[目录名 => 一句人话定位]（与 git 追踪的 16 套完全一致；官网自用主题与 IP 主题不进公开页） */
$QS_THEMES = array(
    'Qsis_business'   => '商务站：首屏卖点、特性、流程、案例、FAQ',
    'Qsis_corp'       => '企业站：品牌介绍 + 产品与服务',
    'Qsis_consulting' => '咨询/律所类：服务项与案例陈述',
    'Qsis_swbj'       => '官网长文版：适合做产品手册与文档入口',
    'blog_default'    => '经典博客：文章流 + 标签页',
    'Qsis_novel'      => '阅读站：长篇连载、章节式浏览',
    'Qsis_art'        => '艺术展示：作品图集为主视觉',
    'Qsis_flower'     => '文艺向：图文并排、留白多',
    'rl_rainbow'      => '文艺向：多彩渐变配色',
    'Qsis_red'        => '经典红：稳重机构站',
    'Qsis_redhead'    => '创意向：大胆排版，适合个人品牌',
    'Qsis_game'       => '游戏/主题站：深色游戏化视觉',
    'Qsis_idol_arena' => '偶像应援 · 舞台站',
    'Qsis_idol_podium' => '偶像应援 · 领奖台版',
    'Qsis_idol_stage' => '偶像应援 · 舞台灯效版',
    'Cn_newyear'      => '节庆专题：春节氛围模板',
);

/* ---------- 1. 页头页脚：取官网现有文章页，保证外观完全一致 ---------- */
$chromeFile = __DIR__ . '/_site_chrome.html';
if (!is_file($chromeFile)) {
    fwrite(STDERR, "缺少页头页脚模板：$chromeFile\n");
    exit(1);
}
$chrome = file_get_contents($chromeFile);
$pArt    = strpos($chrome, '<article class="article-wrap">');
$pFoot   = strpos($chrome, '<footer class="site-footer">');
if ($pArt === false || $pFoot === false) {
    fwrite(STDERR, "页头/页脚标记未找到，模板被人改过？\n");
    exit(1);
}
$headChrome = substr($chrome, 0, $pArt);
$footChrome = substr($chrome, $pFoot);

/* ---------- 2. 收集主题 ---------- */
$cards = array();
$copied = 0;
@mkdir($outDir . '/upload/themes', 0775, true);

foreach ($QS_THEMES as $slug => $desc) {
    $tDir = $QScmsRoot . '/files/theme/' . $slug;
    if (!is_dir($tDir)) { continue; }

    $label = '主题';
    $lf = $tDir . '/theme_label.txt';
    if (is_file($lf)) {
        $l = trim(preg_replace('/\s+/u', '', file_get_contents($lf)));
        if ($l !== '') { $label = $l; }
    }

    $img = '';
    foreach (array('image.jpg', 'image.png', 'preview.jpg', 'screenshot.jpg') as $n) {
        if (is_file($tDir . '/' . $n)) { $img = $tDir . '/' . $n; break; }
    }
    if ($img === '') { continue; }               // 没预览图就不展示，不摆空卡

    $ext = strtolower(pathinfo($img, PATHINFO_EXTENSION));
    $dstName = $slug . '.' . $ext;
    if (!copy($img, $outDir . '/upload/themes/' . $dstName)) {
        fwrite(STDERR, "拷贝预览图失败：$img\n");
        continue;
    }
    $copied++;

    $cards[] = array(
        'slug'  => $slug,
        'label' => $label,
        'desc'  => $desc,
        'img'   => 'upload/themes/' . $dstName,
    );
}

if (!$cards) { fwrite(STDERR, "没有可展示的主题\n"); exit(1); }

/* ---------- 3. 拼正文 ---------- */
$QS_DL = 'https://github.com/isherqiao/QScms/releases/download/v26112303/QScms-26112303.zip';   // 发行包直链
$esc = function ($s) { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); };

$grid = '';
foreach ($cards as $c) {
    $grid .= '            <div class="theme-card">' . "\n"
          . '                <a class="theme-shot" href="' . $esc($c['img']) . '" target="_blank" rel="noopener">'
          . '<img src="' . $esc($c['img']) . '" alt="' . $esc($c['slug']) . ' 主题预览" loading="lazy"></a>' . "\n"
          . '                <div class="theme-body">' . "\n"
          . '                    <div class="theme-head"><h3>' . $esc($c['slug']) . '</h3>'
          . '<span class="theme-chip">' . $esc($c['label']) . '</span></div>' . "\n"
          . '                    <p class="theme-desc">' . $esc($c['desc']) . '</p>' . "\n"
          . '                    <div class="theme-actions">' . "\n"
          . '                        <a class="btn btn-primary btn-sm" href="' . $esc($c['img']) . '" target="_blank" rel="noopener">看大图</a>' . "\n"
          . '                        <a class="btn btn-outline btn-sm" href="' . $esc($QS_DL) . '" target="_blank" rel="noopener">下载</a>' . "\n"
          . '                    </div>' . "\n"
          . '                </div>' . "\n"
          . '            </div>' . "\n";
}

$body = <<<HTML
<!-- 主题模板 -->
<section class="section">
    <div class="container">
        <div class="section-title">
            <span class="eyebrow">Themes</span>
            <h2>主题模板</h2>
            <p>前台主题共 16 套，全部随发行包提供，后台一键切换；主题由 PHP 模板 + CSS 组成，可直接改样式、改布局，不用动核心程序。</p>
        </div>
        <div class="theme-grid">
{$grid}        </div>
        <div class="theme-foot">
            <p>主题在程序目录 <code>files/theme/</code> 下，一个目录就是一套主题；切主题在后台「主题」页完成。</p>
        </div>
    </div>
</section>
<style>
.theme-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(320px,1fr)); gap:28px; }
.theme-card { background:#fff; border:1px solid var(--border-color); border-radius:var(--radius-lg); overflow:hidden; transition:all var(--transition); display:flex; flex-direction:column; }
.theme-card:hover { transform:translateY(-4px); box-shadow:var(--shadow-lg); border-color:transparent; }
.theme-shot { display:block; aspect-ratio:16/10; overflow:hidden; background:var(--bg-soft); }
.theme-shot img { width:100%; height:100%; object-fit:cover; display:block; transition:transform .5s ease; }
.theme-card:hover .theme-shot img { transform:scale(1.04); }
.theme-body { padding:20px 22px 24px; display:flex; flex-direction:column; gap:10px; flex:1; }
.theme-head { display:flex; align-items:center; justify-content:space-between; gap:10px; }
.theme-head h3 { font-size:1.05rem; font-weight:700; margin:0; color:var(--n-900); }
.theme-chip { font-size:.75rem; padding:2px 10px; border-radius:999px; background:var(--c-primary-50); color:var(--c-primary-600); white-space:nowrap; }
.theme-desc { color:var(--text-secondary); font-size:.92rem; line-height:1.65; margin:0; flex:1; }
.theme-actions { display:flex; gap:10px; margin-top:4px; }
.btn-sm { padding:8px 18px; font-size:.85rem; }
.theme-foot { margin-top:36px; color:var(--text-muted); font-size:.9rem; text-align:center; }
.theme-foot code { background:var(--bg-soft); padding:2px 6px; border-radius:4px; }
</style>

HTML;

$html = $headChrome . $body . $footChrome;

/* 标题/描述换成主题页的 */
$html = preg_replace('#<title>.*?</title>#', '<title>主题模板 - QsCms全势内容管理系统</title>', $html, 1);
$html = preg_replace('#<meta name="description" content=".*?">#',
    '<meta name="description" content="QsCms（原QsisCms）前台主题模板展示：16 套开源主题，商务、企业、咨询、博客、阅读、艺术、游戏、应援等场景，随发行包提供，后台一键切换。">',
    $html, 1);
$html = preg_replace('#<link rel="canonical" href="[^"]*">#',
    '<link rel="canonical" href="https://cms.qsis.cn/themes.html">', $html, 1);

file_put_contents($outDir . '/themes.html', $html);
echo "OK themes.html  cards=" . count($cards) . " images=$copied\n";
