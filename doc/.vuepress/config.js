module.exports = {
    title: 'Lodata',
    description: 'The OData v4.01 Producer for Laravel',

    plugins: [
        '@vuepress/back-to-top',
        '@vuepress/nprogress',
        ['@vuepress/google-analytics', {
            ga: 'G-MVEQSHFCV9'
        }],
        ['container', {
            type: 'vue',
            before: '<pre class="vue-container"><code>',
            after: '</code></pre>',
        }],
        ['sitemap', {
            hostname: 'https://lodata.io/',
        }],
    ],

    markdown: {
        lineNumbers: true,
    },

    head: [
        [
            'link',
            {
                href: 'https://fonts.googleapis.com/css?family=Nunito:100,300,400,500,600,700',
                rel: 'stylesheet',
                type: 'text/css',
            },
        ]
    ],

    themeConfig: {
        repo: 'flat3/lodata',

        nav: [
            {
                text: 'OData Specification',
                items: [
                    {
                        text: 'Part 1: Protocol',
                        link: 'https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html',
                        target: '_blank',
                    },
                    {
                        text: 'Part 2: URL Conventions',
                        link: 'https://docs.oasis-open.org/odata/odata/v4.01/os/part2-url-conventions/odata-v4.01-os-part2-url-conventions.html',
                        target: '_blank',
                    },
                    {
                        text: 'JSON Format',
                        link: 'https://docs.oasis-open.org/odata/odata-json-format/v4.01/odata-json-format-v4.01.html',
                        target: '_blank',
                    },
                    {
                        text: 'Common Schema Definition Language: XML',
                        link: 'https://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html',
                        target: '_blank',
                    },
                    {
                        text: 'Common Schema Definition Language: JSON',
                        link: 'https://docs.oasis-open.org/odata/odata-csdl-json/v4.01/odata-csdl-json-v4.01.html',
                        target: '_blank',
                    },
                    {
                        text: 'Vocabularies',
                        link: 'https://docs.oasis-open.org/odata/odata-vocabularies/v4.0/csprd01/odata-vocabularies-v4.0-csprd01.html',
                        target: '_blank',
                    },
                    {
                        text: 'OpenAPI Mapping',
                        link: 'https://docs.oasis-open.org/odata/odata-openapi/v1.0/cn01/odata-openapi-v1.0-cn01.html',
                        target: '_blank',
                    }
                ]
            }
        ],

        sidebar: [
            {
                title: 'Introduction',
                path: '/introduction/',
                collapsable: false,
                children: [
                    'introduction/',
                    'introduction/requirements',
                    'introduction/compliance',
                    'introduction/reporting-issues',
                    'introduction/licence',
                ],
            },
            {
                title: 'Getting Started',
                path: '/getting-started/',
                collapsable: false,
                children: [
                    'getting-started/',
                    'getting-started/configuration',
                    'getting-started/facade',
                    'getting-started/service-provider',
                    'getting-started/authentication',
                    'getting-started/authorization',
                    'getting-started/octane',
                ]
            },
            {
                title: 'Modelling',
                path: '/modelling/',
                collapsable: false,
                children: [
                    'modelling/',
                    {
                        title: 'Entity Sets',
                        path: '/modelling/drivers/',
                        collapsable: true,
                        children: [
                            'modelling/drivers/',
                            'modelling/drivers/eloquent',
                            'modelling/drivers/database',
                            'modelling/drivers/filesystem',
                            'modelling/drivers/redis',
                            'modelling/drivers/collection',
                            'modelling/drivers/csv',
                        ],
                    },
                    'modelling/types',
                    'modelling/relationships',
                    'modelling/operations',
                    'modelling/singletons',
                    'modelling/alternative-keys',
                    'modelling/generated-properties',
                ],
            },
            {
                title: 'Making requests',
                path: '/making-requests/',
                collapsable: false,
                children: [
                    'making-requests/',
                    'making-requests/requesting-data',
                    'making-requests/querying-data',
                    'making-requests/modifying-data',
                    'making-requests/asynchronous-requests',
                    'making-requests/batch',
                    'making-requests/metadata',
                ]
            },
            {
                title: 'Clients',
                path: '/clients/',
                collapsable: false,
                children: [
                    'clients/',
                    'clients/excel',
                    'clients/powerbi',
                    'clients/openapi',
                    'clients/dataverse',
                    'clients/salesforce',
                ],
            },
            {
                title: 'Internals',
                path: '/internals/',
                collapsable: false,
                children: [
                    'internals/',
                    'internals/transactions',
                    'internals/annotations',
                    'internals/expressions',
                    'internals/function-composition',
                    'internals/streaming-json',
                ],
            },
        ]
    },
};
