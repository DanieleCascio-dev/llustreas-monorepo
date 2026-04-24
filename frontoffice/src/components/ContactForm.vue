<script setup>
import { ref, computed } from "vue";

const props = defineProps({
  emailAddress: {
    type: String,
    required: true,
  },
});

const subject = ref("");
const message = ref("");

const mailtoLink = computed(() => {
  const encodedSubject = encodeURIComponent(subject.value);
  const encodedMessage = encodeURIComponent(message.value);
  return `mailto:${props.emailAddress}?subject=${encodedSubject}&body=${encodedMessage}`;
});

function sendEmail() {
  window.location.href = mailtoLink.value;
}
</script>

<template>
  <section class="contact-me-section">
    <div class="contact-me-inner">
      <h2 v-reveal class="contact-me-title title-custom">Contattami</h2>
      <p v-reveal class="contact-me-text text-custom">
        Hai un progetto in mente o semplicemente vuoi salutarmi? Scrivimi!
      </p>
      <form v-reveal @submit.prevent="sendEmail" class="contact-form">
        <div class="form-group">
          <label for="subject" class="sr-only">Oggetto</label>
          <input
            type="text"
            id="subject"
            v-model="subject"
            placeholder="Oggetto dell'email"
            class="form-control"
            required
          />
        </div>
        <div class="form-group">
          <label for="message" class="sr-only">Messaggio</label>
          <textarea
            id="message"
            v-model="message"
            placeholder="Scrivi qui il tuo messaggio"
            rows="6"
            class="form-control"
            required
          ></textarea>
        </div>
        <button type="submit" class="submit-btn subtitle-custom">
          Invia Email
        </button>
      </form>
    </div>
  </section>
</template>

<style scoped lang="scss">
@use "../style/partials/variables" as *;

.contact-me-section {
  background-color: $bg-light;
  padding: 80px 16px;
  text-align: center;
  position: relative;
  overflow: hidden;

  @media (min-width: 768px) {
    padding: 100px 24px;
  }
}

.contact-me-inner {
  max-width: 768px;
  margin: 0 auto;
}

.contact-me-title {
  color: $text-violet;
  margin-bottom: 24px;
}

.contact-me-text {
  color: $text-color;
  margin-bottom: 40px;
  line-height: 1.6;
}

.contact-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
  text-align: left;
}

.form-group {
  margin-bottom: 0;
}

.form-control {
  width: 100%;
  padding: 12px 16px;
  border: 1px solid $text-violet;
  border-radius: 8px;
  font-family: "Work Sans", sans-serif;
  font-size: $text-size;
  color: $text-violet;
  background-color: $bg-white;
  transition:
    border-color 0.3s ease,
    box-shadow 0.3s ease;

  &:focus {
    outline: none;
    border-color: darken($text-violet, 10%);
    box-shadow: 0 0 0 3px rgba($text-violet, 0.2);
  }

  &::placeholder {
    color: lighten($text-color, 20%);
  }
}

textarea.form-control {
  resize: vertical;
  min-height: 120px;
}

.submit-btn {
  display: flex;
  justify-content: center;
  align-items: center;
  background-image: url("../assets/img/backgrounds/SFONDI_PULSANTI_02_VIOLA.svg"); /* Placeholder, use actual image */
  background-size: contain;
  background-repeat: no-repeat;
  background-position: center;
  width: 100%;
  height: 80px;
  color: $bg-white;
  font-family: "Young Serif", serif;
  border: none;
  cursor: pointer;
  -webkit-tap-highlight-color: transparent;
  transition:
    color 0.3s ease,
    background-image 0.3s ease;
  font-size: clamp(1.6rem, 4vw, 2rem);
  letter-spacing: 0.05em;

  @media (min-width: 768px) {
    max-width: 300px; /* Adjust as needed for desktop */
    margin: 0 auto;
    height: 90px;
  }

  @media (hover: hover) {
    &:hover {
      color: $text-violet;
      background-image: url("../assets/img/backgrounds/SFONDI_PULSANTI_02_BIANCO.svg"); /* Placeholder, use actual image */
    }
  }

  &:active {
    opacity: 0.85;
  }
}

.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}
</style>
