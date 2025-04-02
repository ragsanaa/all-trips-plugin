(() => {
  "use strict";
  const t = (t) => {
      if (t.endsWith("wetravel.com")) return "https://t.wetravel.com/widgets";
      return `https://t.${t
        .replace(/https?:\/\//, "")
        .split(".")
        .slice(-3)
        .join(".")}/widgets`;
    },
    e = [];
  let o = {},
    n = {};
  const i = window.location.origin.includes("wetravel"),
    s = (e, o, n, i, s) => {
      if (!i) return;
      const d = {
        version: i,
        user_id: n,
        embed_type: "button",
        action_type: e,
        options: { _env_: o },
      };
      let r = "";
      "undefined" != typeof window &&
        window.location &&
        (r = window.location.href),
        (d.url = r);
      ((t, e, o, n, i = "GET") => {
        if (!window.XMLHttpRequest) return;
        const s = new XMLHttpRequest();
        (s.onload = () => {
          e && "function" == typeof e && e(s.responseXML);
        }),
          s.open(i, t),
          (s.responseType = "document"),
          n && s.setRequestHeader(...n),
          o ? s.send(o) : s.send();
      })(
        t(o),
        s,
        JSON.stringify(d),
        ["Content-Type", "application/json"],
        "POST"
      );
    },
    d = () => {
      const t = document.querySelectorAll(".wtrvl-checkout_button");
      [].forEach.call(t, (t, o) => {
        const d = t;
        t.isTracked ||
          (i ||
            0 != o ||
            s("load", t.dataset.env, t.dataset.uid, t.dataset.version),
          t.addEventListener("click", (o) => {
            if (
              (i || s("click", t.dataset.env, t.dataset.uid, t.dataset.version),
              !document.querySelector('iframe[class="wtrvl-ifrm"]') &&
                o.target.classList.contains("wtrvl-checkout_button"))
            ) {
              const t = ((t) => {
                  const e = document.createElement("iframe");
                  return (
                    e.setAttribute(
                      "style",
                      "position:fixed;width: 100vw; height:100vh;height:100dvh;top:0;left:0;bottom:0;right:0;z-index:21150313555"
                    ),
                    e.setAttribute("frameborder", "0"),
                    e.setAttribute("src", t),
                    e.setAttribute("class", "wtrvl-ifrm"),
                    e
                  );
                })(o.target.getAttribute("href")),
                s = document.createElement("style");
              (s.innerHTML = "body > *:not(.wtrvl-ifrm){display:none}"),
                window &&
                  window.innerWidth <= 991 &&
                  document.body.appendChild(s),
                document.body.appendChild(t),
                i
                  ? setTimeout(() => {
                      t.contentWindow.postMessage("tripPage", "*");
                    }, 3e3)
                  : setTimeout(() => {
                      t.contentWindow.postMessage("buttonWidget", "*");
                    }, 3e3),
                e.push(t),
                (n = s),
                (document.body.style.overflow = "hidden"),
                (document.body.style.height = "auto");
            }
            o.stopPropagation(), o.preventDefault();
          })),
          (d.isTracked = !0);
      });
    },
    r = () => {
      e.map(
        (t) => (
          t.remove ? t.remove() : t.parentNode && t.parentNode.removeChild(t),
          n.parentNode && n.parentNode.removeChild(n),
          ((t) => {
            document.body.setAttribute("style", t);
          })(o),
          t
        )
      );
    };
  document.addEventListener("DOMContentLoaded", () => {
    setTimeout(() => {
      (o = document.body.getAttribute("style")),
        d(),
        (() => {
          const t = window.addEventListener
            ? "addEventListener"
            : "attachEvent";
          (0, window[t])("attachEvent" === t ? "onmessage" : "message", (t) => {
            if (
              ("wtrvlCheckoutClosed" === t.data && r(), "fileUpload" === t.data)
            ) {
              const t = document.querySelector(".wtrvl-ifrm");
              t && (t.style.height = "100%");
            }
          });
        })();
    }, 0);
  });
})();
