ajax.get('/api/group_confirmation_queue/',function(data) {
    var group_confirmation_queue_form_fields = [
        {type:"hidden", name:"id"},
        {type:"identity", name:"identity_id",required:true, label:"Identity", template:"{{#attributes.identity}}{{first_name}} {{last_name}}{{/attributes.identity}}"},
        {name:"group_id","label":"Group",type:"select",options:"/api/groups",format:{label:"{{name}}", value:"{{id}}"}},
        {name:"action","label":"Action",type:"select",options:[{label:"Add",value:"add"},{label:"Remove",value:"remove"}]},
        {name:"created_at","label":"Date",type:"output",show:false,parse:false}
    ];

    gdg = new GrapheneDataGrid({el:'#adminDataGrid',
        item_template: gform.stencils['table_row'],
        search: false,columns: false,upload:false,download:false,title:'Queue',
        entries:[],
        sortBy: 'order',
        actions:actions,
        count:20,
        schema:group_confirmation_queue_form_fields, 
        data: data
    });
});