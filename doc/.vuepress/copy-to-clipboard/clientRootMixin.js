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
            jQuery('div[class*="language-"]:not(.code-copy-added)').each((i, el) => {
                const $el = jQuery(el);
                let ComponentClass = Vue.extend(CodeCopy);
                let instance = new ComponentClass();

                instance.code = $el.find('code').text();
                instance.parent = el;
                instance.$mount();
                $el.addClass('code-copy-added');
                $el.append(instance.$el);
            })
        }
    }
}
