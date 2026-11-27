# QScms 官网（cms.qsis.cn）

QScms / Qsiscms 的产品官网，纯静态站，托管在 GitHub Pages，自定义域名 `cms.qsis.cn`。

它是**独立项目**，不属于 QScms 程序本体：产品代码只管程序，官网只管站点。以前官网页面是塞在 QScms 仓库 `tools/` 里跑的，改得越多越容易互相污染，所以拆出来单独维护。

## 本地环境

| 项 | 值 |
|---|---|
| 源码目录 | `G:/GitHub/CMS/qscms-site`（本仓库） |
| 本地访问 | http://127.0.0.1:1112/ |
| 虚拟主机 | `G:/xampp/apache/conf/extra/httpd-vhosts.conf` 中 `*:1112`，`Listen 1112` 在 `httpd.conf` |
| 发布方式 | `git push` 到 `main`，GitHub Pages 自动构建，约 1 分钟后生效 |

## 目录结构

```
/                 站点根：index.html、features.html、themes.html、quick-start.html、docs/ 等
/article/         文章详情页
/tag/ /tags/      标签页
/upload/          站点图片，含 upload/themes/ 下的 16 张主题预览图
/files/           站点静态资源
/tools/           页面生成器（不在发布链路上跑，本地用）
  build_themes_page.php   扫 QScms 主题目录 → 生成 themes.html + 拷预览图
  build_static_pages.php  把 tools/pages/*_body.html 套页头页脚 → quick-start.html、docs/index.html
  pages/                  两个页面的正文片段
  _site_chrome.html       页头页脚模板（取自线上 features.html，改全站头尾就改这里）
```

## 改页面的正确姿势

- **主题模板页**：改 `tools/build_themes_page.php` 里的主题白名单/文案，然后跑生成器；不要去手改 `themes.html`，下次生成会覆盖。
- **快速开始 / 使用文档**：改 `tools/pages/quickstart_body.html`、`tools/pages/docs_body.html`，再跑 `build_static_pages.php`。
- **页头页脚、导航、页脚链接**：改 `tools/_site_chrome.html`（只影响上面三个生成页），其余存量静态页要同步手改。
- **其余存量页面**：直接改 HTML，提交推送即可。

两个生成器的目标目录默认就是本仓库根目录，QScms 程序目录自动探测同级目录（也可显式指定）：

```
php tools/build_themes_page.php [输出目录] [QScms 程序目录]
php tools/build_static_pages.php [输出目录]
```

QScms 程序目录也可以用环境变量 `QS_CMS_ROOT` 指定；找不到时脚本直接报错退出，不会瞎猜。

## 边界

- 主题页只列**公开的 16 套主题**（白名单写在 `build_themes_page.php` 里）。QScms 官网自用主题 `Qsis_cms` 和 17 套 IP/私货主题（柯南、高达、EVA、火影、LABUBU 等）一律不出现。
- 文档页内容由《QScms使用手册》重写而来，已剔除本机路径、后台文件名清单、版本号等不宜公开的信息。往里加内容时保持同样尺度。
- 站点**不提供在线 demo**。演示站要一台常开的服务器，攻击面太大，README 与官网页都只给本地部署路径。

## 已知

- 本仓库当前是浅克隆（只拉了最近几个提交）。要完整历史：`git fetch --unshallow`（github.com 直连不稳，走 `ssh.github.com:443`）。
- 推送通道：`GIT_SSH_COMMAND` 指定 `~/.ssh/id_ed25519_gh_admin` + 443 端口，remote 为 `git@ssh.github.com:isher/cms.git`。
- 这三个生成页目前**不由 QScms 内容数据生成**，将来若对官网做全量 SSG 重建，需要把生成器接进构建流程，否则会被覆盖。
