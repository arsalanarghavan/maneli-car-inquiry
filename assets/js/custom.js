(function () {
  "use strict";

  // Suppress defaultmenu errors
  window.addEventListener('error', function(e) {
    if (e.filename && e.filename.includes('defaultmenu.min.js')) {
      console.warn('DefaultMenu error suppressed:', e.message);
      e.preventDefault();
      e.stopPropagation();
      return true;
    }
  }, true);

  /* page loader */
  function hideLoader() {
    const loader = document.getElementById("loader");
    loader.classList.add("d-none")
  }

  window.addEventListener("load", hideLoader);
  /* page loader */

  /* tooltip */
  const tooltipTriggerList = document.querySelectorAll(
    '[data-bs-toggle="tooltip"]'
  );
  if (tooltipTriggerList && tooltipTriggerList.length > 0 && typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
    const tooltipList = Array.from(tooltipTriggerList).map(
      (tooltipTriggerEl) => new bootstrap.Tooltip(tooltipTriggerEl)
    );
  }

  /* popover  */
  const popoverTriggerList = document.querySelectorAll(
    '[data-bs-toggle="popover"]'
  );
  if (popoverTriggerList && popoverTriggerList.length > 0 && typeof bootstrap !== 'undefined' && bootstrap.Popover) {
    const popoverList = Array.from(popoverTriggerList).map(
      (popoverTriggerEl) => new bootstrap.Popover(popoverTriggerEl)
    );
  }

  
  /* breadcrumb date range picker */
  if (document.querySelector("#switcher-canvas")) {
    //switcher color pickers
    const pickrContainerPrimary = document.querySelector(
      ".pickr-container-primary"
    );
    const themeContainerPrimary = document.querySelector(
      ".theme-container-primary"
    );
    const pickrContainerBackground = document.querySelector(
      ".pickr-container-background"
    );
    const themeContainerBackground = document.querySelector(
      ".theme-container-background"
    );

    /* for theme primary */
    const nanoThemes = [
      [
        "nano",
        {
          defaultRepresentation: "RGB",
          components: {
            preview: true,
            opacity: false,
            hue: true,

            interaction: {
              hex: false,
              rgba: true,
              hsva: false,
              input: true,
              clear: false,
              save: false,
            },
          },
        },
      ],
    ];
    const nanoButtons = [];
    let nanoPickr = null;
    for (const [theme, config] of nanoThemes) {
      const button = document.createElement("button");
      button.innerHTML = theme;
      nanoButtons.push(button);

      button.addEventListener("click", () => {
        const el = document.createElement("p");
        pickrContainerPrimary.appendChild(el);

        /* Delete previous instance */
        if (nanoPickr) {
          nanoPickr.destroyAndRemove();
        }

        /* Apply active class */
        for (const btn of nanoButtons) {
          btn.classList[btn === button ? "add" : "remove"]("active");
        }

        /* Create fresh instance */
        nanoPickr = new Pickr(
          Object.assign(
            {
              el,
              theme,
              default: "#2D89BE",
            },
            config
          )
        );

        /* Set events */
        nanoPickr.on("changestop", (source, instance) => {
          let color = instance.getColor().toRGBA();
          let html = document.querySelector("html");
          html.style.setProperty(
            "--primary-rgb",
            `${Math.floor(color[0])}, ${Math.floor(color[1])}, ${Math.floor(color[2])}`
          );
          /* theme color picker */
          localStorage.setItem(
            "primaryRGB",
            `${Math.floor(color[0])}, ${Math.floor(color[1])}, ${Math.floor(color[2])}`
          );
          // updateColors();
        });
      });

      themeContainerPrimary.appendChild(button);
    }
    nanoButtons[0].click();
    /* for theme primary */

    /* for theme background */
    const nanoThemes1 = [
      [
        "nano",
        {
          defaultRepresentation: "RGB",
          components: {
            preview: true,
            opacity: false,
            hue: true,

            interaction: {
              hex: false,
              rgba: true,
              hsva: false,
              input: true,
              clear: false,
              save: false,
            },
          },
        },
      ],
    ];
    const nanoButtons1 = [];
    let nanoPickr1 = null;
    for (const [theme, config] of nanoThemes) {
      const button = document.createElement("button");
      button.innerHTML = theme;
      nanoButtons1.push(button);

      button.addEventListener("click", () => {
        const el = document.createElement("p");
        pickrContainerBackground.appendChild(el);

        /* Delete previous instance */
        if (nanoPickr1) {
          nanoPickr1.destroyAndRemove();
        }

        /* Apply active class */
        for (const btn of nanoButtons) {
          btn.classList[btn === button ? "add" : "remove"]("active");
        }

        /* Create fresh instance */
        nanoPickr1 = new Pickr(
          Object.assign(
            {
              el,
              theme,
              default: "#2D89BE",
            },
            config
          )
        );

        /* Set events */
        nanoPickr1.on("changestop", (source, instance) => {
          let color = instance.getColor().toRGBA();
          let html = document.querySelector("html");
          html.style.setProperty(
            "--body-bg-rgb",
            `${color[0]}, ${color[1]}, ${color[2]}`
          );
          document
            .querySelector("html")
            .style.setProperty(
              "--body-bg-rgb2",
              `${color[0] + 14}, ${color[1] + 14}, ${color[2] + 14}`
            );
          document
            .querySelector("html")
            .style.setProperty(
              "--light-rgb",
              `${color[0] + 14}, ${color[1] + 14}, ${color[2] + 14}`
            );
          document
            .querySelector("html")
            .style.setProperty(
              "--form-control-bg",
              `rgb(${color[0] + 14}, ${color[1] + 14}, ${color[2] + 14})`
            );
            document
              .querySelector("html")
              .style.setProperty(
                "--gray-3",
                `rgb(${color[0] + 14}, ${color[1] + 14}, ${color[2] + 14})`
              );
          localStorage.removeItem("bgtheme");
          // updateColors();
          html.setAttribute("data-theme-mode", "dark");
          html.setAttribute("data-menu-styles", "dark");
          html.setAttribute("data-header-styles", "dark");
          document.querySelector("#switcher-dark-theme").checked = true;
          localStorage.setItem(
            "bodyBgRGB",
            `${color[0]}, ${color[1]}, ${color[2]}`
          );
          localStorage.setItem(
            "bodylightRGB",
            `${color[0] + 14}, ${color[1] + 14}, ${color[2] + 14}`
          );
        });
      });
      themeContainerBackground.appendChild(button);
    }
    nanoButtons1[0].click();
    /* for theme background */
  }

  /**
   * Update header icon colors and borders based on the current theme variables.
   * The icons occasionally fail to pick up the new CSS variable values when the theme
   * is toggled via JavaScript, so we explicitly set them after every toggle.
   */
  function updateHeaderIconsTheme() {
    const html = document.documentElement;
    if (!html) {
      return;
    }

    const computed = window.getComputedStyle(html);
    const iconColor =
      (computed.getPropertyValue("--header-prime-color") ||
        computed.getPropertyValue("--default-text-color") ||
        "").trim();
    const borderColor =
      (computed.getPropertyValue("--header-border-color") || "").trim();

    if (!iconColor && !borderColor) {
      return;
    }

    document
      .querySelectorAll(".main-header-container .header-link-icon")
      .forEach((icon) => {
        icon.style.color = iconColor;
        if (borderColor) {
          icon.style.borderColor = borderColor;
        }

        if (icon.tagName.toLowerCase() === "svg") {
          icon.style.stroke = iconColor;
          icon
            .querySelectorAll(
              "path, circle, rect, line, polyline, polygon, ellipse"
            )
            .forEach((shape) => {
              shape.style.stroke = iconColor;
              const fillAttr = shape.getAttribute("fill");
              if (!fillAttr || fillAttr === "currentColor") {
                shape.style.fill = iconColor;
              }
            });
        }
      });
  }

  window.maneliUpdateHeaderIcons = function () {
    window.requestAnimationFrame(updateHeaderIconsTheme);
  };

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", window.maneliUpdateHeaderIcons);
  } else {
    window.maneliUpdateHeaderIcons();
  }

  window.addEventListener("load", window.maneliUpdateHeaderIcons);

  /* header theme toggle */
  function toggleTheme() {
    let html = document.querySelector("html");
    if (html.getAttribute("data-theme-mode") === "dark") {
      html.setAttribute("data-theme-mode", "light");
      html.setAttribute("data-header-styles", "light");
      html.setAttribute("data-menu-styles", "dark");
      if (!localStorage.getItem("primaryRGB")) {
        html.setAttribute("style", "");
      }
      html.removeAttribute("data-bg-theme");
      if (document.querySelector("#switcher-canvas")) {
        document.querySelector("#switcher-light-theme").checked = true;
        document.querySelector("#switcher-menu-dark").checked = true;
      }
      document .querySelector("html") .style.removeProperty("--body-bg-rgb", localStorage.bodyBgRGB);
      // checkOptions();
      html.style.removeProperty("--body-bg-rgb2");
      html.style.removeProperty("--light-rgb");
      html.style.removeProperty("--form-control-bg");
      html.style.removeProperty("--input-border");
      
      if (document.querySelector("#switcher-canvas")) {
        document.querySelector("#switcher-header-light").checked = true;
        document.querySelector("#switcher-menu-light").checked = true;
        document.querySelector("#switcher-light-theme").checked = true;
        document.querySelector("#switcher-background4").checked = false;
        document.querySelector("#switcher-background3").checked = false;
        document.querySelector("#switcher-background2").checked = false;
        document.querySelector("#switcher-background1").checked = false;
        document.querySelector("#switcher-background").checked = false;
      }
      localStorage.removeItem("xintradarktheme");
      localStorage.removeItem("xintraMenu");
      localStorage.removeItem("xintraHeader");
      localStorage.removeItem("bodylightRGB");
      localStorage.removeItem("bodyBgRGB");
      html.setAttribute("data-header-styles", "light");
    } else {
      html.setAttribute("data-theme-mode", "dark");
      html.setAttribute("data-header-styles", "dark");
      html.setAttribute("data-menu-styles", "dark");
      if (!localStorage.getItem("primaryRGB")) {
        html.setAttribute("style", "");
      }
      
      if (document.querySelector("#switcher-canvas")) {
        document.querySelector("#switcher-dark-theme").checked = true;
        document.querySelector("#switcher-menu-dark").checked = true;
        document.querySelector("#switcher-header-dark").checked = true;
        // checkOptions();
        document.querySelector("#switcher-menu-dark").checked = true;
        document.querySelector("#switcher-header-dark").checked = true;
        document.querySelector("#switcher-dark-theme").checked = true;
        document.querySelector("#switcher-background4").checked = false;
        document.querySelector("#switcher-background3").checked = false;
        document.querySelector("#switcher-background2").checked = false;
        document.querySelector("#switcher-background1").checked = false;
        document.querySelector("#switcher-background").checked = false;
      }
      localStorage.setItem("xintradarktheme", "true");
      localStorage.setItem("xintraMenu", "dark");
      localStorage.setItem("xintraHeader", "dark");
      localStorage.removeItem("bodylightRGB");
      localStorage.removeItem("bodyBgRGB");
    }

    if (window.maneliUpdateHeaderIcons) {
      window.maneliUpdateHeaderIcons();
    }
  }
  // Dark mode toggle - initialize with better timing and multiple retries
  let darkModeToggleInitialized = false;
  let darkModeToggleRetries = 0;
  const MAX_RETRIES = 20; // Try for up to 2 seconds (20 * 100ms)
  
  function initDarkModeToggle() {
    // Prevent multiple initializations
    if (darkModeToggleInitialized) {
      return;
    }
    
    let layoutSetting = document.querySelector(".layout-setting");
    
    if (layoutSetting) {
      console.log("Found .layout-setting element, initializing dark mode toggle");
      
      // Remove any existing listeners by cloning
      const parent = layoutSetting.parentNode;
      if (!parent) {
        console.warn("Parent not found for .layout-setting");
        return;
      }
      
      const newLayoutSetting = layoutSetting.cloneNode(true);
      parent.replaceChild(newLayoutSetting, layoutSetting);
      
      // Attach our listener
      newLayoutSetting.addEventListener("click", function(e) {
        e.preventDefault();
        e.stopPropagation();
        console.log("Dark mode toggle clicked from custom.js");
        toggleTheme();
        return false;
      });
      
      darkModeToggleInitialized = true;
      console.log("Dark mode toggle initialized successfully from custom.js");
    } else {
      darkModeToggleRetries++;
      if (darkModeToggleRetries < MAX_RETRIES) {
        console.log("Element .layout-setting not found, retrying... (" + darkModeToggleRetries + "/" + MAX_RETRIES + ")");
        setTimeout(initDarkModeToggle, 100);
      } else {
        console.error("Failed to initialize dark mode toggle: element .layout-setting not found after " + MAX_RETRIES + " retries");
      }
    }
  }
  
  // Initialize immediately and also on DOM ready
  console.log("Setting up dark mode toggle initialization...");
  initDarkModeToggle(); // Try immediately
  
  // Also try on DOMContentLoaded
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
      console.log("DOMContentLoaded fired, retrying dark mode toggle initialization");
      setTimeout(initDarkModeToggle, 50);
    });
  }
  
  // Also try after window load as fallback
  window.addEventListener('load', function() {
    if (!darkModeToggleInitialized) {
      console.log("Window load fired, final attempt to initialize dark mode toggle");
      setTimeout(initDarkModeToggle, 100);
    }
  });
  /* header theme toggle */

  /* Choices JS */
  document.addEventListener("DOMContentLoaded", function () {
    var genericExamples = document.querySelectorAll("[data-trigger]");
    for (let i = 0; i < genericExamples.length; ++i) {
      var element = genericExamples[i];
      new Choices(element, {
        allowHTML: true,
        placeholderValue: "This is a placeholder set in the config",
        searchPlaceholderValue: "Search",
      });
    }
  });
  /* Choices JS */

  /* footer year */
  const yearElement = document.getElementById("year");
  if (yearElement) {
    yearElement.innerHTML = new Date().getFullYear();
  }
  /* footer year */

  /* node waves */
  Waves.attach(".btn-wave", ["waves-light"]);
  Waves.init();
  /* node waves */

  /* card with close button */
  let DIV_CARD = ".card";
  let cardRemoveBtn = document.querySelectorAll(
    '[data-bs-toggle="card-remove"]'
  );
  cardRemoveBtn.forEach((ele) => {
    ele.addEventListener("click", function (e) {
      e.preventDefault();
      let $this = this;
      let card = $this.closest(DIV_CARD);
      card.remove();
      return false;
    });
  });
  /* card with close button */

  /* card with fullscreen */
  let cardFullscreenBtn = document.querySelectorAll(
    '[data-bs-toggle="card-fullscreen"]'
  );
  cardFullscreenBtn.forEach((ele) => {
    ele.addEventListener("click", function (e) {
      let $this = this;
      let card = $this.closest(DIV_CARD);
      card.classList.toggle("card-fullscreen");
      card.classList.remove("card-collapsed");
      e.preventDefault();
      return false;
    });
  });
  /* card with fullscreen */

  /* count-up */
  var i = 1;
  setInterval(() => {
    document.querySelectorAll(".count-up").forEach((ele) => {
      if (ele.getAttribute("data-count") >= i) {
        i = i + 1;
        ele.innerText = i;
      }
    });
  }, 10);
  /* count-up */

  /* back to top */
  const scrollToTop = document.querySelector(".scrollToTop");
  const $rootElement = document.documentElement;
  const $body = document.body;
  window.onscroll = () => {
    const scrollTop = window.scrollY || window.pageYOffset;
    const clientHt = $rootElement.scrollHeight - $rootElement.clientHeight;
    if (window.scrollY > 100) {
      scrollToTop.style.display = "flex";
    } else {
      scrollToTop.style.display = "none";
    }
  };
  scrollToTop.onclick = () => {
    window.scrollTo(0, 0);
  };
  /* back to top */

  /* header dropdowns scroll */
  var myHeadernotification = document.getElementById("header-notification-scroll");
  if (myHeadernotification) {
    new SimpleBar(myHeadernotification, { autoHide: true });
  }

  var myHeaderCart = document.getElementById("header-cart-items-scroll");
  if (myHeaderCart) {
    new SimpleBar(myHeaderCart, { autoHide: true });
  }
  /* header dropdowns scroll */

  const autoCompleteJS = new autoComplete({
    selector: "#header-search",
    data: {
      src: [
        "What is the meaning of life?",
        "How does gravity work?",
        "Why is the sky blue?",
        "What is the capital of France?",
        "Who painted the Mona Lisa?",
        "What is the speed of light?",
        "Why do we dream?",
        "How do birds fly?",
        "What is the largest mammal?",
        "Why do leaves change color in the fall?"
      ],
      cache: true,
    },
    resultItem: {
      highlight: true
    },
    events: {
      input: {
        selection: (event) => {
          const selection = event.detail.selection.value;
          autoCompleteJS.input.value = selection;
        }
      }
    }
  });
})();

/* full screen */
var elem = document.documentElement;
window.openFullscreen = function() {
  if (!document.fullscreenElement && !document.webkitFullscreenElement && !document.msFullscreenElement) {
    requestFullscreen();
  } else {
    exitFullscreen();
  }
}
function requestFullscreen() {
  if (elem.requestFullscreen) {
    elem.requestFullscreen();
  } else if (elem.webkitRequestFullscreen) {
    elem.webkitRequestFullscreen();
  } else if (elem.msRequestFullscreen) {
    elem.msRequestFullscreen();
  }
}
function exitFullscreen() {
  if (document.exitFullscreen) {
    document.exitFullscreen();
  } else if (document.webkitExitFullscreen) {
    document.webkitExitFullscreen();
  } else if (document.msExitFullscreen) {
    document.msExitFullscreen();
  }
}
// Listen for fullscreen change event
document.addEventListener("fullscreenchange", handleFullscreenChange);
function handleFullscreenChange() {
  
  let open = document.querySelector(".full-screen-open");
  let close = document.querySelector(".full-screen-close");

  if (document.fullscreenElement || document.webkitFullscreenElement || document.msFullscreenElement) {
    // Update icon for fullscreen mode
    close.classList.add("d-block");
    close.classList.remove("d-none");
    open.classList.add("d-none");
  } else {
    // Update icon for non-fullscreen mode
    close.classList.remove("d-block");
    open.classList.remove("d-none");
    close.classList.add("d-none");
    open.classList.add("d-block");
  }
}
/* full screen */

/* toggle switches */
let customSwitch = document.querySelectorAll(".toggle");
customSwitch.forEach((e) =>
  e.addEventListener("click", () => {
    e.classList.toggle("on");
  })
);
/* toggle switches */

/* header dropdown close button */

/* for cart dropdown */
const headerbtn = document.querySelectorAll(".dropdown-item-close");
headerbtn.forEach((button) => {
  button.addEventListener("click", (e) => {
    e.preventDefault();
    e.stopPropagation();
    button.parentNode.parentNode.parentNode.parentNode.parentNode.remove();
    document.getElementById("cart-data").innerText = `${document.querySelectorAll(".dropdown-item-close").length
      } `;
    document.getElementById("cart-icon-badge").innerText = `${document.querySelectorAll(".dropdown-item-close").length
      }`;
    var headerCartScroll = document.getElementById("header-cart-items-scroll");
    if (headerCartScroll) {
      console.log(headerCartScroll.children.length);
    }
    if (document.querySelectorAll(".dropdown-item-close").length == 0) {
      let elementHide = document.querySelector(".empty-header-item");
      let elementShow = document.querySelector(".empty-item");
      elementHide.classList.add("d-none");
      elementShow.classList.remove("d-none");
    }
  });
});
/* for cart dropdown */

/* for notifications dropdown */
const headerbtn1 = document.querySelectorAll(".dropdown-item-close1");
headerbtn1.forEach((button) => {
  button.addEventListener("click", (e) => {
    e.preventDefault();
    e.stopPropagation();
    button.parentNode.parentNode.parentNode.parentNode.remove();
    document.getElementById("notifiation-data").innerText = `${document.querySelectorAll(".dropdown-item-close1").length
      } خوانده نشده`;
    if (document.querySelectorAll(".dropdown-item-close1").length == 0) {
      let elementHide1 = document.querySelector(".empty-header-item1");
      let elementShow1 = document.querySelector(".empty-item1");
      elementHide1.classList.add("d-none");
      elementShow1.classList.remove("d-none");
    }
  });
});
/* for notifications dropdown */
