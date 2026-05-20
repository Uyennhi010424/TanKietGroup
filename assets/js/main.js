const menuButton = document.querySelector("[data-menu-toggle]");
const nav = document.querySelector("[data-main-nav]");

if (menuButton && nav) {
  menuButton.addEventListener("click", () => {
    const isOpen = nav.classList.toggle("open");
    menuButton.setAttribute("aria-expanded", String(isOpen));
    // animate hamburger
    menuButton.classList.toggle('open', isOpen);
    // when opening mobile menu, close any open mega-dropdowns so menu shows full list
    if (isOpen) {
      document.querySelectorAll('.has-dropdown.open').forEach(el => {
        el.classList.remove('open');
        const t = el.querySelector('[data-dropdown-toggle]');
        if (t) t.setAttribute('aria-expanded', 'false');
      });
    }
  });

  window.addEventListener("resize", () => {
    if (window.innerWidth > 980) {
      nav.classList.remove("open");
      menuButton.setAttribute("aria-expanded", "false");
    }
  });
}

const revealItems = document.querySelectorAll(".reveal");
if ("IntersectionObserver" in window && revealItems.length > 0) {
  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add("show");
          observer.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.15 }
  );

  revealItems.forEach((item) => observer.observe(item));
}

const faqButtons = document.querySelectorAll("[data-faq-trigger]");
faqButtons.forEach((button) => {
  button.addEventListener("click", () => {
    const item = button.closest(".faq-item");
    if (!item) {
      return;
    }

    const parent = item.parentElement;
    if (parent) {
      parent.querySelectorAll(".faq-item").forEach((sibling) => {
        if (sibling !== item) {
          sibling.classList.remove("open");
        }
      });
    }

    item.classList.toggle("open");
  });
});

const forms = document.querySelectorAll("[data-demo-form]");
forms.forEach((form) => {
  form.addEventListener("submit", (event) => {
    event.preventDefault();
    const submitButton = form.querySelector("button[type='submit']");
    if (!submitButton) {
      return;
    }

    const originalLabel = submitButton.textContent;
    submitButton.disabled = true;
    submitButton.textContent = "Đang gửi...";

    window.setTimeout(() => {
      submitButton.style.background = "var(--success)";
      submitButton.textContent = "Gửi thành công";

      window.setTimeout(() => {
        submitButton.disabled = false;
        submitButton.style.background = "";
        submitButton.textContent = originalLabel || "Gửi";
        form.reset();
      }, 1400);
    }, 900);
  });
});

const smoothAnchors = document.querySelectorAll("a[href^='#']");
smoothAnchors.forEach((anchor) => {
  anchor.addEventListener("click", (event) => {
    const targetSelector = anchor.getAttribute("href");
    if (!targetSelector || targetSelector.length < 2) {
      return;
    }

    const target = document.querySelector(targetSelector);
    if (!target) {
      return;
    }

    event.preventDefault();
    target.scrollIntoView({ behavior: "smooth", block: "start" });
  });
});

// Dropdown (mega menu) toggle handling
const dropdownToggles = document.querySelectorAll('[data-dropdown-toggle]');
dropdownToggles.forEach(btn => {
  btn.addEventListener('click', (e) => {
    e.stopPropagation();
    const li = btn.closest('.has-dropdown');
    if (!li) return;
    const isOpen = li.classList.toggle('open');
    btn.setAttribute('aria-expanded', String(isOpen));

    // close other dropdowns
    document.querySelectorAll('.has-dropdown').forEach(other => {
      if (other !== li) {
        other.classList.remove('open');
        const t = other.querySelector('[data-dropdown-toggle]');
        if (t) t.setAttribute('aria-expanded', 'false');
      }
    });
  });
});

// Close dropdowns when clicking outside
document.addEventListener('click', (e) => {
  document.querySelectorAll('.has-dropdown.open').forEach(el => {
    if (!el.contains(e.target)) {
      el.classList.remove('open');
      const t = el.querySelector('[data-dropdown-toggle]');
      if (t) t.setAttribute('aria-expanded', 'false');
    }
  });
});

// Make hotline clickable behavior for mobile: nothing extra needed, ensure tel: link exists
