(window.webpackJsonp=window.webpackJsonp||[]).push([[8],{444:function(t,e,a){t.exports=a.p+"assets/img/powerbi1.a1df842e.png"},445:function(t,e,a){t.exports=a.p+"assets/img/powerbi2.63896fa0.png"},446:function(t,e,a){t.exports=a.p+"assets/img/powerbi3.5bcfcf6a.png"},447:function(t,e,a){t.exports=a.p+"assets/img/powerbi4.8a67f312.png"},485:function(t,e,a){"use strict";a.r(e);var o=a(25),s=Object(o.a)({},(function(){var t=this,e=t.$createElement,o=t._self._c||e;return o("ContentSlotsDistributor",{attrs:{"slot-key":t.$parent.slotKey}},[o("h1",{attrs:{id:"microsoft-powerbi"}},[o("a",{staticClass:"header-anchor",attrs:{href:"#microsoft-powerbi"}},[t._v("#")]),t._v(" Microsoft PowerBI")]),t._v(" "),o("p",[t._v("Microsoft "),o("a",{attrs:{href:"https://powerbi.microsoft.com",target:"_blank",rel:"noopener noreferrer"}},[t._v("Power BI"),o("OutboundLink")],1),t._v(" supports creating connections to OData Feeds. Once your Power BI\nreport is created it can be published into the "),o("a",{attrs:{href:"https://docs.microsoft.com/en-us/power-bi/fundamentals/power-bi-service-overview",target:"_blank",rel:"noopener noreferrer"}},[t._v("PowerBI Service"),o("OutboundLink")],1),t._v("\nwhere it can be configured to automatically refresh itself in the cloud.")]),t._v(" "),o("h2",{attrs:{id:"connect-manually"}},[o("a",{staticClass:"header-anchor",attrs:{href:"#connect-manually"}},[t._v("#")]),t._v(" Connect manually")]),t._v(" "),o("p",[t._v('An OData model can be imported into Power BI using the following steps. Once imported the model can be updated\nby clicking the "Refresh" button on the toolbar. Power BI understands OData types and will automatically type the columns\naccording to the schema. Power BI can also recognise and import the relationships between models.')]),t._v(" "),o("h3",{attrs:{id:"step-1-get-data-from-odata-feed"}},[o("a",{staticClass:"header-anchor",attrs:{href:"#step-1-get-data-from-odata-feed"}},[t._v("#")]),t._v(" Step 1 - Get Data from OData Feed")]),t._v(" "),o("p",[o("img",{attrs:{src:a(444),alt:"Get data"}})]),t._v(" "),o("hr"),t._v(" "),o("h3",{attrs:{id:"step-2-supply-the-odata-endpoint"}},[o("a",{staticClass:"header-anchor",attrs:{href:"#step-2-supply-the-odata-endpoint"}},[t._v("#")]),t._v(" Step 2 - Supply the OData endpoint")]),t._v(" "),o("p",[t._v("At this stage if Lodata has authentication configured the user will be prompted for their credentials. Credentials\nare not stored in the report. If the report is sent to another user they will be prompted to authenticate\nwhen they open it.")]),t._v(" "),o("p",[o("img",{attrs:{src:a(445),alt:"Endpoint"}})]),t._v(" "),o("hr"),t._v(" "),o("h3",{attrs:{id:"step-3-choose-the-sets"}},[o("a",{staticClass:"header-anchor",attrs:{href:"#step-3-choose-the-sets"}},[t._v("#")]),t._v(" Step 3 - Choose the sets")]),t._v(" "),o("p",[t._v("Power BI parses the metadata document and identifies importable sets. Choosing the multiple items\nwill enable Power BI to import several sets at once, and to automatically import any relationships that\nexist between them.")]),t._v(" "),o("p",[o("img",{attrs:{src:a(446),alt:"Set"}})]),t._v(" "),o("hr"),t._v(" "),o("h3",{attrs:{id:"step-4-load-the-data"}},[o("a",{staticClass:"header-anchor",attrs:{href:"#step-4-load-the-data"}},[t._v("#")]),t._v(" Step 4 - Load the data")]),t._v(" "),o("p",[t._v("Once the data connection is made, you can use the data to build reports.")]),t._v(" "),o("p",[o("img",{attrs:{src:a(447),alt:"Load"}})]),t._v(" "),o("h2",{attrs:{id:"connect-automatically"}},[o("a",{staticClass:"header-anchor",attrs:{href:"#connect-automatically"}},[t._v("#")]),t._v(" Connect automatically")]),t._v(" "),o("p",[t._v("Microsoft "),o("a",{attrs:{href:"https://powerbi.microsoft.com",target:"_blank",rel:"noopener noreferrer"}},[t._v("PowerBI"),o("OutboundLink")],1),t._v(" supports creating a live connection to an OData source via its\n"),o("a",{attrs:{href:"https://docs.microsoft.com/en-us/power-bi/connect-data/desktop-data-sources#using-pbids-files-to-get-data",target:"_blank",rel:"noopener noreferrer"}},[t._v("PBIDS"),o("OutboundLink")],1),t._v("\ndocument format.")]),t._v(" "),o("p",[t._v('The URL to the PBIDS document can be used in a "Connect to PowerBI" feature button. Unlike Excel which works on a single\nentity set, this URL provides PowerBI with access to the whole model:\n'),o("a",{attrs:{href:"http://127.0.0.1:8000/odata/_lodata/odata.pbids",target:"_blank",rel:"noopener noreferrer"}},[o("code",[t._v("http://127.0.0.1:8000/odata/_lodata/odata.pbids")]),o("OutboundLink")],1)]),t._v(" "),o("p",[t._v("This URL can be programmatically generated:")]),t._v(" "),o("div",{staticClass:"language-php line-numbers-mode"},[o("pre",{pre:!0,attrs:{class:"language-php"}},[o("code",[o("span",{pre:!0,attrs:{class:"token class-name class-name-fully-qualified static-context"}},[o("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v("\\")]),t._v("Lodata")]),o("span",{pre:!0,attrs:{class:"token operator"}},[t._v("::")]),o("span",{pre:!0,attrs:{class:"token function"}},[t._v("getPbidsUrl")]),o("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v("(")]),o("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v(")")]),t._v("\n")])]),t._v(" "),o("div",{staticClass:"line-numbers-wrapper"},[o("span",{staticClass:"line-number"},[t._v("1")]),o("br")])])])}),[],!1,null,null,null);e.default=s.exports}}]);