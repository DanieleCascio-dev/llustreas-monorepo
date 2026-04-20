<script setup>
const year = new Date().getFullYear()

const emailParts = ['illust.reas', 'gmail', 'com']

function emailAddress() {
  return `${emailParts[0]}@${emailParts[1]}.${emailParts[2]}`
}

function openEmail() {
  window.location.href = `mailto:${emailAddress()}`
}
</script>

<template>
  <footer class="site-footer">
    <div class="footer-inner">
      <div class="footer-contact">
        <h3 class="subtitle-custom">Vuoi contattarmi?</h3>
        <p class="text-custom">
          Scrivimi:<br />
          <strong>
            <button type="button" class="email-link text-custom" @click="openEmail">
              <span>{{ emailParts[0] }}</span><span aria-hidden="true">@</span><span>{{ emailParts[1] }}</span><span aria-hidden="true">.</span><span>{{ emailParts[2] }}</span>
            </button>
          </strong>
        </p>
      </div>

      <div class="footer-social">
        <h3 class="subtitle-custom">Seguimi!</h3>
        <div class="social-icons">
          <a href="https://www.instagram.com/illust.reas/" target="_blank" rel="noopener noreferrer" class="social-link" aria-label="Instagram">
            <img src="../assets/img/icone/instagram.svg" alt="Instagram" />
          </a>
          <a href="https://www.behance.net/letiziaragione" target="_blank" rel="noopener noreferrer" class="social-link" aria-label="Behance">
            <img src="../assets/img/icone/behance.svg" alt="Behance" />
          </a>
          <a href="https://www.linkedin.com/in/letizia-ragione/" target="_blank" rel="noopener noreferrer" class="social-link" aria-label="LinkedIn">
            <img src="../assets/img/icone/linkedin.svg" alt="LinkedIn" />
          </a>
        </div>
      </div>
    </div>

    <div class="footer-bottom">
      <div class="footer-divider"></div>
      <div class="footer-bottom-inner">
        <p class="footer-copyright">&copy; {{ year }} illust.reas &mdash; Letizia Ragione</p>
        <p class="footer-credit">crafted by Daniele Cascio</p>
      </div>
    </div>
  </footer>
</template>

<style scoped lang="scss">
@use "../style/partials/variables" as *;

.site-footer {
  background-color: $footer-bg;
  color: $bg-light;
  padding: 40px 20px;
  padding-bottom: calc(40px + env(safe-area-inset-bottom, 0px));

  @media (min-width: 768px) {
    padding: 48px 100px;
    padding-bottom: calc(48px + env(safe-area-inset-bottom, 0px));
  }

  @media (min-width: 1024px) {
    padding: 48px 200px;
    padding-bottom: calc(48px + env(safe-area-inset-bottom, 0px));
  }
}

.footer-inner {
  display: flex;
  flex-direction: column;
  gap: 32px;

  @media (min-width: 768px) {
    flex-direction: row;
    justify-content: space-between;
    align-items: flex-end;
  }
}

.footer-contact {
  a,
  .email-link {
    color: $bg-light;
    text-decoration: none;

    &:active {
      text-decoration: underline;
    }

    @media (hover: hover) {
      &:hover {
        text-decoration: underline;
      }
    }
  }

  .email-link {
    display: inline;
    padding: 0;
    border: 0;
    background: transparent;
    font: inherit;
    cursor: pointer;
  }
}

.footer-social {
  @media (min-width: 768px) {
    text-align: right;
  }
}

.social-icons {
  display: flex;
  gap: 16px;
  margin-top: 8px;

  @media (min-width: 768px) {
    justify-content: flex-end;
  }
}

/* ── Footer bottom ── */
.footer-bottom {
  margin-top: 36px;
}

.footer-divider {
  height: 1px;
  background: $bg-light;
  opacity: 0.12;
  margin-bottom: 20px;
}

.footer-bottom-inner {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;

  @media (min-width: 768px) {
    flex-direction: row;
    justify-content: space-between;
  }
}

.footer-copyright {
  margin: 0;
  font-size: 0.8rem;
  letter-spacing: 0.03em;
  opacity: 0.6;
}

.footer-credit {
  margin: 0;
  font-size: 0.72rem;
  letter-spacing: 0.05em;
  opacity: 0.4;

  a {
    color: $bg-light;
    text-decoration: none;
    transition: opacity 0.2s;

    @media (hover: hover) {
      &:hover {
        opacity: 0.8;
        text-decoration: underline;
      }
    }
  }
}

/* Min 44x44 touch area */
.social-link {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 48px;
  height: 48px;
  -webkit-tap-highlight-color: transparent;

  img {
    width: 36px;
    height: 36px;
  }

  &:active {
    opacity: 0.7;
  }

  @media (min-width: 768px) {
    width: 60px;
    height: 60px;

    img {
      width: 48px;
      height: 48px;
    }
  }
}
</style>
