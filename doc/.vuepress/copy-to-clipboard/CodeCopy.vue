<template>
  <div @click="copyToClipboard" class="code-copy absolute flex items-center right-10 z-10" style="top: 0.6rem">
    <span :class="copying ? '' : 'hidden'" class="transition-opacity duration-500 mr-2 text-xs font-sans text-gray-500">Copied!</span>
    <svg aria-hidden="true" viewBox="0 0 16 16" version="1.1" data-view-component="true"
         height="16" width="16" class="opacity-75 cursor-pointer hover:opacity-100">
      <path fill="#0396a6" fill-rule="evenodd"
            d="M5.75 1a.75.75 0 00-.75.75v3c0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75v-3a.75.75 0 00-.75-.75h-4.5zm.75 3V2.5h3V4h-3zm-2.874-.467a.75.75 0 00-.752-1.298A1.75 1.75 0 002 3.75v9.5c0 .966.784 1.75 1.75 1.75h8.5A1.75 1.75 0 0014 13.25v-9.5a1.75 1.75 0 00-.874-1.515.75.75 0 10-.752 1.298.25.25 0 01.126.217v9.5a.25.25 0 01-.25.25h-8.5a.25.25 0 01-.25-.25v-9.5a.25.25 0 01.126-.217z"></path>
    </svg>
  </div>
</template>

<script>
export default {
  props: {
    parent: Object,
    code: String,
  },
  data() {
    return {
      copying: false,
    }
  },
  methods: {
    copyToClipboard(el) {
      const label = jQuery(this.$el).closest('[data-event-label]').data('event-label');

      window.gtag && window.gtag('event', 'copy', label && {'event_label': label});

      if (navigator.clipboard) {
        navigator.clipboard.writeText(this.code).then(
            () => {
              this.setSuccessTransitions()
            },
            () => {
            }
        )
        return;
      }

      let copyelement = document.createElement('textarea')
      document.body.appendChild(copyelement)
      copyelement.value = this.code
      copyelement.select()
      document.execCommand('Copy')
      copyelement.remove()

      this.setSuccessTransitions()
    },
    setSuccessTransitions() {
      clearTimeout(this.copyingTimeout)

      this.copying = true
      this.copyingTimeout = setTimeout(() => {
        this.copying = false
      }, 1500)
    }
  }
}
</script>
