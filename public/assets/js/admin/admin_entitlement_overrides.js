overrides_template = `
<div id="adminDataGrid"></div>
`
ajax.get('/api/entitlements/'+id+'/overrides',function(data) {
    gdg = new GrapheneDataGrid({el:'#adminDataGrid',
        name: 'entitlement_overrides',
        search: false,columns: false,upload:false,download:true,title:'Entitlement Overrides',
        count:20,
        actions:[],
        schema:[
            {type:"hidden", name:"id"},
            {type: 'identity',label: "Identity",name:"identity_id",
                template:"{{#attributes.identity}}{{first_name}} {{last_name}}{{/attributes.identity}}"
            },
            {type:"text", name:"type", label:"Type"},
            {type: 'identity',label: "Sponsor",name:"sponsor_id",
                template:"{{#attributes.sponsor}}{{first_name}} {{last_name}}{{/attributes.sponsor}}"
            },
            {type:"text", name:"description", label:"Description"},
            {type:"date", label: "Expiration Date",name: "expiration_date"}
        ],
        data: data
    }).on('click',function(event) {
        window.location = '/identities/'+event.model.attributes.identity_id
    })
});