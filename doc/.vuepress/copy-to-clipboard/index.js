const { path } = require('@vuepress/shared-utils')

module.exports = (options = {}, ctx) => ({
    enhanceAppFiles: [path.resolve(__dirname, 'appFile.js')],
    clientRootMixin: path.resolve(__dirname, 'clientRootMixin.js')
})
