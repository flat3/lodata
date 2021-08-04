import CodeCopy from './CodeCopy.vue'
import Vue from 'vue'

export default {
    ready() {
        this.update()
    },
    updated() {
        this.update()
    },
    methods: {
        update() {
            document.querySelectorAll('div[class*="language-"] pre:not(.code-copy-added)').forEach(el => {
                let ComponentClass = Vue.extend(CodeCopy)
                let instance = new ComponentClass()

                instance.code = el.innerText
                instance.parent = el
                instance.$mount()
                el.classList.add('code-copy-added')
                el.appendChild(instance.$el)
            })
        }
    }
}
