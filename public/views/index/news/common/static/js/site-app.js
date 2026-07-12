/* 前台公共逻辑（纯 JS，勿放入 .html 模板，避免 ThinkPHP 误解析 url()/模板字符串） */
(function () {
  const cfg = window.__SITE_CFG__ || {};

  const applyDataBg = function () {
    document.querySelectorAll("[data-bg]").forEach(function (el) {
      var bg = el.getAttribute("data-bg");
      if (bg) {
        el.style.backgroundImage = "u" + "rl('" + String(bg).replace(/'/g, "\\'") + "')";
      }
    });
  };
  applyDataBg();

  const { createApp, ref, onMounted, onUnmounted } = Vue;

  window.__SITE_APP__ = createApp({
    setup: function () {
      const priceVisible = ref(false);
      const themeKey = "seagull-theme";

      const readTheme = function () {
        try {
          var saved = localStorage.getItem(themeKey);
          if (saved === "light" || saved === "dark") return saved;
        } catch (e) {}
        return window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
      };

      const themeMode = ref(readTheme());
      const applyTheme = function (mode) {
        document.documentElement.setAttribute("data-theme", mode);
        try {
          localStorage.setItem(themeKey, mode);
        } catch (e) {}
      };
      applyTheme(themeMode.value);

      const toggleTheme = function () {
        themeMode.value = themeMode.value === "dark" ? "light" : "dark";
        applyTheme(themeMode.value);
      };

      const elementOpacity = ref(0);
      const scrollThreshold = ref(150);
      const keyword = ref(cfg.keyword || "");
      const qcodeVisible = ref(false);
      const layerVisible = ref(false);
      const content = ref("");
      const load = ref(false);
      const drawer = ref(false);
      const rankList = ref([]);
      const rankDj = ref([]);
      const is_m = ref(0);
      const newList = ref([]);
      const QList = ref([]);
      const QLoading = ref(false);
      const total_result = ref(0);
      const currentSource = ref(0);
      const dialogUrl = ref(false);
      const dialogLoading = ref(false);
      const dialogItem = ref({});
      const is_type = ref(0);
      const pc_type = ref(0);

      // 与 PHP detectResourceKind 对齐：扩展名优先，小说≠影视，禁止「小说」误伤电影名
      const resourceKind = function (title, link) {
        title = title || "";
        link = link || "";
        var raw = title + " " + link;
        var text = raw.toLowerCase();
        var hasNovelExt = /\.(txt|epub|mobi|azw3|umd)(?:\b|$|[^\w])/i.test(text);
        var hasVideoExt = /\.(mp4|mkv|m3u8|avi|mov|wmv|flv|rmvb)(?:\b|$|[^\w])/i.test(text);
        if (hasNovelExt && !hasVideoExt) return { key: "novel", label: "小说阅读" };
        if (hasVideoExt && !hasNovelExt) return { key: "video", label: "影视视频" };

        var videoHard = ["1080p", "720p", "4k", "蓝光", "电影", "电视剧", "短剧", "综艺", "动漫", "国漫", "剧场版", "更新至", "更至", "年番", "web-4k", "web-dl", "hdr", "杜比", "remux"];
        var novelHard = ["完本", "全本", "网文", "电子书", "精校", "女频", "男频", "网络小说", "小说txt", "txt全集", "小说下载", "小说合集", "修仙小说", "言情小说", "玄幻小说"];
        var hasVideoHard = videoHard.some(function (k) { return text.indexOf(k) !== -1; });
        var hasNovelHard = novelHard.some(function (k) { return text.indexOf(k) !== -1; });
        if (!hasNovelHard && !hasVideoHard) {
          if (/(?:^|[\s\[【(（_\-])小说(?:$|[\s\]】)）_\-])/.test(raw) || /小说完整版|小说全集|长篇小说|网络小说/.test(raw)) {
            hasNovelHard = true;
          }
        }
        if (hasNovelHard && !hasVideoHard) return { key: "novel", label: "小说阅读" };
        if (hasVideoHard && !hasNovelHard) return { key: "video", label: "影视视频" };
        if (hasNovelHard && hasVideoHard) {
          if (/1080p|4k|蓝光|web-?dl|remux|更新至/i.test(text)) return { key: "video", label: "影视视频" };
          return { key: "novel", label: "小说阅读" };
        }

        var scored = [
          ["document", "学习文档", [".pdf", ".doc", ".docx", ".ppt", "教程", "课件", "资料", "论文"]],
          ["software", "软件工具", [".exe", ".dmg", ".apk", "软件", "应用", "插件", "源码", "绿色版"]],
          ["archive", "压缩资源", [".zip", ".rar", ".7z", "合集", "打包"]],
          ["image", "图片素材", [".jpg", ".png", "壁纸", "素材", "图包"]],
          ["novel", "小说阅读", ["网文", "言情", "玄幻", "修仙", "完本", "全本", "电子书", ".txt", ".epub"]],
          ["video", "影视视频", [".mp4", ".mkv", "电影", "电视剧", "短剧", "综艺", "动漫", "1080p", "4k", "蓝光", "剧集"]],
        ];
        var best = { key: "other", label: "其他", score: 0 };
        for (var i = 0; i < scored.length; i++) {
          var sc = 0;
          for (var j = 0; j < scored[i][2].length; j++) {
            var kw = scored[i][2][j];
            if (text.indexOf(kw) !== -1) sc += kw.charAt(0) === "." ? 5 : 2;
          }
          if (sc > best.score) best = { key: scored[i][0], label: scored[i][1], score: sc };
        }
        return best.score > 0 ? { key: best.key, label: best.label } : { key: "other", label: "其他" };
      };

      const showMessage = function (message, type) {
        type = type || "info";
        ElementPlus.ElMessage({ message: message, type: type, plain: true });
      };

      const closeMobileFilter = function () {
        var boxElement = document.querySelector(".listBox .screen .fixed .box");
        var screen = document.querySelector(".listBox .screen");
        var mask = document.querySelector(".listBox .screen .filter-mask");
        if (boxElement) {
          boxElement.classList.remove("is-open");
          boxElement.style.display = "";
        }
        if (screen) screen.classList.remove("is-filter-open");
        if (mask) mask.classList.remove("is-open");
      };

      const ensureFilterMask = function () {
        var screen = document.querySelector(".listBox .screen");
        if (!screen) return null;
        var mask = screen.querySelector(".filter-mask");
        if (!mask) {
          mask = document.createElement("div");
          mask.className = "filter-mask";
          mask.addEventListener("click", function (e) {
            e.preventDefault();
            e.stopPropagation();
            closeMobileFilter();
          });
          screen.insertBefore(mask, screen.firstChild);
        }
        return mask;
      };

      const handleScroll = function () {
        var scrollTop = window.scrollY || document.documentElement.scrollTop;
        elementOpacity.value =
          scrollTop >= scrollThreshold.value
            ? Math.min((scrollTop - scrollThreshold.value) / 100, 1)
            : Math.max(1 - (scrollThreshold.value - scrollTop) / 100, 0);

        // 顶栏滚动态：中高级站常见玻璃吸顶
        if (scrollTop > 12) document.body.classList.add("is-scrolled");
        else document.body.classList.remove("is-scrolled");

        // 滚动时收起移动端分类面板（仅在打开时操作）
        if (is_m.value) {
          var openBox = document.querySelector(".listBox .screen .fixed .box.is-open");
          if (openBox) closeMobileFilter();
        }
      };

      const searchBtn = function () {
        if (!keyword.value) {
          return showMessage(cfg.search_input_empty || "请输入你要搜索的内容~", "error");
        }
        var current = window.location.href;
        var target = "/s/" + keyword.value + ".html";
        if (current.indexOf("/s/") !== -1 || current.indexOf("/d/") !== -1) {
          window.location.href = target;
        } else {
          window.open(target, "_blank");
        }
      };

      const saveBtn = async function () {
        if (!content.value) {
          return showMessage(cfg.demand_input_empty || "请输入你想看的资源信息~", "error");
        }
        if (load.value) return;
        load.value = true;
        try {
          var response = await axios.post("/api/tool/feedback", { content: content.value });
          showMessage(response.data.message, response.data.code === 200 ? "success" : "error");
          if (response.data.code === 200) {
            layerVisible.value = false;
            content.value = "";
          }
        } finally {
          load.value = false;
        }
      };

      const setnum = function (num) {
        return (num / 10000).toFixed(2) + "W";
      };

      const goLink = function (event, id) {
        event.preventDefault();
        window.location.href = "/d/" + id + ".html";
      };

      const changeBtn = function (e) {
        var category_id = cfg.category_id || "";
        if (category_id) {
          window.location.href = "/s/" + keyword.value + "-" + e + "-" + category_id + ".html";
        } else {
          window.location.href = "/s/" + keyword.value + "-" + e + ".html";
        }
      };

      const copyText = async function (event, title, link, code) {
        event.preventDefault();
        var text = "标题：" + title + "\n链接：" + link;
        if (code) text += "\n提取码：" + code;
        text += "\n由【" + (cfg.app_name || "") + " " + window.location.hostname + "】提供网盘分享链接";
        try {
          await navigator.clipboard.writeText(text);
          showMessage("已复制分享文案", "success");
        } catch (err) {
          var textArea = document.createElement("textarea");
          textArea.value = text;
          textArea.style.position = "fixed";
          textArea.style.opacity = 0;
          document.body.appendChild(textArea);
          textArea.focus();
          textArea.select();
          try {
            if (document.execCommand("copy")) showMessage("已复制分享文案", "success");
            else throw new Error("复制失败");
          } catch (e2) {
            showMessage("复制失败，请手动复制", "error");
          }
          document.body.removeChild(textArea);
        }
      };

      const selectBtn = function () {
        if (!is_m.value) return;
        var boxElement = document.querySelector(".listBox .screen .fixed .box");
        var screen = document.querySelector(".listBox .screen");
        if (!boxElement) return;
        var open = !boxElement.classList.contains("is-open");
        if (open) {
          boxElement.classList.add("is-open");
          if (screen) screen.classList.add("is-filter-open");
          var mask = ensureFilterMask();
          if (mask) mask.classList.add("is-open");
        } else {
          closeMobileFilter();
        }
      };

      const handleDeviceType = function () {
        var isMobile = window.matchMedia("(max-width: 768px)").matches;
        if (isMobile) {
          is_m.value = 1;
          pc_type.value = 1;
        } else {
          is_m.value = 0;
          pc_type.value = cfg.pc_type || 0;
        }
      };

      const closePricePop = function () {
        priceVisible.value = false;
        localStorage.setItem("pricePopDate", new Date().toLocaleDateString());
      };

      onMounted(function () {
        applyDataBg();
        handleDeviceType();
        applyTheme(themeMode.value);

        try {
          if (!localStorage.getItem(themeKey)) {
            var mq = window.matchMedia("(prefers-color-scheme: dark)");
            var onScheme = function (e) {
              if (localStorage.getItem(themeKey)) return;
              themeMode.value = e.matches ? "dark" : "light";
              applyTheme(themeMode.value);
            };
            if (mq.addEventListener) mq.addEventListener("change", onScheme);
            else if (mq.addListener) mq.addListener(onScheme);
          }
        } catch (e) {}

        var path = window.location.pathname;
        var isHomePage = path === "/" || path === "/index.html";
        // 公告：首页每天最多弹一次，避免每次进站打断搜索
        try {
          var today = new Date().toLocaleDateString();
          var shown = localStorage.getItem("pricePopDate");
          priceVisible.value = !!(isHomePage && shown !== today);
        } catch (e) {
          priceVisible.value = !!isHomePage;
        }

        handleScroll();
        window.addEventListener("scroll", handleScroll, { passive: true });
        window.addEventListener("resize", handleDeviceType);

        // 类型芯片平滑滚动
        document.querySelectorAll('a.kind-chip[href^="#"]').forEach(function (a) {
          a.addEventListener("click", function (ev) {
            var id = a.getAttribute("href");
            if (!id || id.length < 2) return;
            var el = document.querySelector(id);
            if (!el) return;
            ev.preventDefault();
            el.scrollIntoView({ behavior: "smooth", block: "start" });
          });
        });

        // 不再给内容加 opacity:0 入场类，避免首页/搜索只剩搜索框
        try {
          document.querySelectorAll(".reveal, .will-animate").forEach(function (n) {
            n.classList.add("is-in");
            n.classList.remove("will-animate");
            n.style.opacity = "1";
            n.style.visibility = "visible";
            n.style.transform = "none";
          });
        } catch (e) {}

        // 快捷键 / 聚焦主搜索（输入框内除外）
        window.addEventListener("keydown", function (e) {
          if (e.key === "/" && !e.metaKey && !e.ctrlKey && !e.altKey) {
            var tag = (e.target && e.target.tagName) || "";
            if (tag === "INPUT" || tag === "TEXTAREA" || (e.target && e.target.isContentEditable)) return;
            var input =
              document.querySelector(".js-main-search") ||
              document.querySelector(".premium-search input") ||
              document.querySelector(".searchBox .search input");
            if (input) {
              e.preventDefault();
              input.focus();
              if (input.select) input.select();
            }
          }
        });

        // 回顶按钮
        var backTop = document.getElementById("pro-backtop");
        if (!backTop) {
          backTop = document.createElement("button");
          backTop.type = "button";
          backTop.id = "pro-backtop";
          backTop.className = "pro-backtop";
          backTop.setAttribute("aria-label", "回到顶部");
          backTop.innerHTML = "↑";
          document.body.appendChild(backTop);
          backTop.addEventListener("click", function () {
            window.scrollTo({ top: 0, behavior: "smooth" });
          });
        }
        var syncBackTop = function () {
          var y = window.scrollY || document.documentElement.scrollTop;
          if (y > 420) backTop.classList.add("is-show");
          else backTop.classList.remove("is-show");
        };
        syncBackTop();
        window.addEventListener("scroll", syncBackTop, { passive: true });

        document.body.classList.add("pro-ready");

        // 首页类型专区：自动拉取最新内容并静默更新（后台入库会清缓存）
        try {
          var path = window.location.pathname || "/";
          var isHome = path === "/" || path === "/index.html" || path.indexOf("/index") === 0;
          if (isHome && window.axios) {
            var refreshKindModules = function (force) {
              var url = "/api/tool/kindModules" + (force ? "?refresh=1" : "");
              axios
                .get(url)
                .then(function (res) {
                  if (!res || !res.data || res.data.code !== 200) return;
                  var modules = (res.data.data && res.data.data.modules) || [];
                  if (!modules.length) return;
                  var grid = document.querySelector(".kind-module-grid");
                  if (!grid) return;
                  var html = "";
                  modules.forEach(function (km) {
                    var listHtml = "";
                    if (km.list && km.list.length) {
                      listHtml = '<ol class="kind-module-list">';
                      km.list.forEach(function (item, idx) {
                        listHtml +=
                          "<li><b>" +
                          (idx + 1) +
                          '</b><a href="/d/' +
                          item.id +
                          '.html" target="_blank">' +
                          String(item.title || "")
                            .replace(/&/g, "&amp;")
                            .replace(/</g, "&lt;")
                            .replace(/>/g, "&gt;") +
                          "</a></li>";
                      });
                      listHtml += "</ol>";
                    } else {
                      listHtml =
                        '<div class="kind-empty"><p>库中暂未匹配到此类标题关键词</p><a class="kind-empty-btn" href="/s/' +
                        encodeURIComponent(km.search || "") +
                        '.html">搜索「' +
                        String(km.search || "") +
                        "」→</a></div>";
                    }
                    html +=
                      '<article class="kind-module kind-' +
                      km.key +
                      '" id="kind-' +
                      km.key +
                      '"><header><div class="kind-module-icon"></div><div class="kind-module-titles"><h3>' +
                      String(km.label || "") +
                      "</h3><p>" +
                      String(km.subtitle || "") +
                      '</p></div><a class="kind-more" href="/s/' +
                      encodeURIComponent(km.search || "") +
                      '.html">更多 →</a></header>' +
                      listHtml +
                      "</article>";
                  });
                  // 静默替换，无花哨动画
                  grid.innerHTML = html;
                  var tip = document.getElementById("kind-auto-tip");
                  if (tip && res.data.data && res.data.data.updated_at) {
                    tip.textContent = "内容已自动更新 · " + res.data.data.updated_at;
                  }
                })
                .catch(function () {});
            };
            // 进入首页后拉取一次；之后每 5 分钟自动刷新
            setTimeout(function () {
              refreshKindModules(false);
            }, 1500);
            setInterval(function () {
              refreshKindModules(false);
            }, 5 * 60 * 1000);
          }
        } catch (e) {}
      });

      onUnmounted(function () {
        window.removeEventListener("scroll", handleScroll);
        window.removeEventListener("resize", handleDeviceType);
      });

      return {
        elementOpacity: elementOpacity,
        scrollThreshold: scrollThreshold,
        keyword: keyword,
        searchBtn: searchBtn,
        rankList: rankList,
        newList: newList,
        setnum: setnum,
        qcodeVisible: qcodeVisible,
        layerVisible: layerVisible,
        content: content,
        saveBtn: saveBtn,
        rankDj: rankDj,
        goLink: goLink,
        changeBtn: changeBtn,
        copyText: copyText,
        drawer: drawer,
        selectBtn: selectBtn,
        is_m: is_m,
        QList: QList,
        QLoading: QLoading,
        total_result: total_result,
        currentSource: currentSource,
        dialogUrl: dialogUrl,
        dialogLoading: dialogLoading,
        dialogItem: dialogItem,
        is_type: is_type,
        pc_type: pc_type,
        resourceKind: resourceKind,
        priceVisible: priceVisible,
        closePricePop: closePricePop,
        themeMode: themeMode,
        toggleTheme: toggleTheme,
      };
    },
  })
    .use(ElementPlus)
    .mount("#app");

  // 兼容旧页面里的 app.xxx 写法
  window.app = window.__SITE_APP__;
})();
