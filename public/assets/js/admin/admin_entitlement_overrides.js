overrides_template = `
<div id="adminDataGrid"></div>
`
ajax.get('/api/entitlements/'+id+'/overrides',function(data) {
    gdg = new GrapheneDataGrid({el:'#adminDataGrid',
        item_template: gform.stencils['table_row'],
        search: false,columns: false,upload:false,download:true,title:'Entitlement Overrides',
        count:20,
        actions:[],
        schema:[
            {type:"hidden", name:"id"},
            {type: 'identity',label: "Identity",name:"identity_id",
                template:"{{#attributes.identity}}{{first_name}} {{last_name}}{{/attributes.identity}}"
            },
            {type:"text", name:"type", label:"Type"},
            {type:"date", label: "Expiration Date",name: "expiration_date"},
            {type: 'identity',label: "Sponsor",name:"sponsor_id",
                template:"{{#attributes.sponsor}}{{first_name}} {{last_name}}{{/attributes.sponsor}}"
            },
            {type: 'switch',label: "Renewable",name:"sponsor_renew_allow",
                options:[{value:'false',label:"False"},{value:'true',label:"True"}]
            },
            {type: 'text',label: "Renew Dates Allowed",name:"sponsor_renew_days"},
        ],
        data: data
    })
});