<?php
/*
 * QScms (Qsiscms) - GPL-3.0-or-later
 * 把 tools/pages/*_body.html 套上官网页头页脚，输出到目标站点目录。
 * 用法：php tools/build_static_pages.php <目标站点目录>
 */
if (PHP_SAPI !== 'cli') { exit('CLI only'); }

/* 输出站点目录：默认就是本脚本所在的官网项目根目录，可用第 1 个参数覆盖 */
$outDir = $argv[1] ?? dirname(__DIR__);
if (!is_dir($outDir)) {
    fwrite(STDERR, "目标站点目录不存在：$outDir\n用法：php tools/build_static_pages.php [目标站点目录]\n");
    exit(1);
}
$outDir = rtrim(str_replace('\\', '/', realpath($outDir)), '/');

$chrome = file_get_contents(__DIR__ . '/_site_chrome.html');
$pArt   = strpos($chrome, '<article class="article-wrap">');
$pFoot  = strpos($chrome, '<footer class="site-footer">');
if ($pArt === false || $pFoot === false) { fwrite(STDERR, "模板标记未找到\n"); exit(1); }
$head = substr($chrome, 0, $pArt);
$foot = substr($chrome, $pFoot);

$pages = array(
    'quick-start.html' => array(
        'body'      => 'pages/quickstart_body.html',
        'title'     => '快速开始 - QsCms全势内容管理系统',
        'desc'      => 'QScms 快速开始：环境要求、上传部署、安装向导四步、装完必做事项、Apache 与 Nginx 伪静态配置、常见报错处理。',
        'canonical' => 'https://cms.qsis.cn/quick-start.html',
    ),
    'docs/index.html' => array(
        'body'      => 'pages/docs_body.html',
        'title'     => '使用文档 - QsCms全势内容管理系统',
        'desc'      => 'QScms 使用文档：系统概览、目录结构、安装部署、后台功能、数据存储、主题与多语言、静态化 SSG、AI 发文接口、安全加固、日常操作与排错速查。',
        'canonical' => 'https://cms.qsis.cn/docs/',
    ),
);

foreach ($pages as $rel => $cfg) {
    $bodyFile = __DIR__ . '/' . $cfg['body'];
    if (!is_file($bodyFile)) { fwrite(STDERR, "缺正文：$bodyFile\n"); continue; }

    $html = $head . file_get_contents($bodyFile) . $foot;
    $html = preg_replace('#<title>.*?</title>#', '<title>' . $cfg['title'] . '</title>', $html, 1);
    $html = preg_replace('#<meta name="description" content=".*?">#',
        '<meta name="description" content="' . htmlspecialchars($cfg['desc'], ENT_QUOTES, 'UTF-8') . '">', $html, 1);
    $html = preg_replace('#<link rel="canonical" href="[^"]*">#',
        '<link rel="canonical" href="' . $cfg['canonical'] . '">', $html, 1);

    $target = $outDir . '/' . $rel;
    @mkdir(dirname($target), 0775, true);
    file_put_contents($target, $html);
    echo "OK $rel  " . strlen($html) . " bytes\n";
}
