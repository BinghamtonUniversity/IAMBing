ajax.get('/api/group_action_queue/',function(data) {
    var group_action_queue_form_fields = [
        {type:"hidden", name:"id"},
        {name:"action","label":"Action",type:"select",options:[{label:"Add",value:"add"},{label:"Remove",value:"remove"}]},
        {type:"identity", name:"identity_id",required:true, label:"Identity", template:"{{#attributes.identity}}{{first_name}} {{last_name}}{{/attributes.identity}}"},
        {name:"group_id","label":"Group",type:"select",options:"/api/groups",format:{label:"{{name}}", value:"{{id}}"}},
        {name:"created_at","label":"Date",type:"output",show:false,parse:false}
    ];

    gdg = new GrapheneDataGrid({el:'#adminDataGrid',
        item_template: gform.stencils['table_row'],
        search: false,columns: false,upload:false,download:false,title:'Queue',
        entries:[],
        sortBy: 'order',
        actions:actions,
        count:20,
        schema:group_action_queue_form_fields, 
        data: data
    }).on('execute',function(event) {
        var models = event.grid.getSelected();
        var action_queue_ids = _.map(models,function(model) {
            return model.attributes.id
        })
        if (action_queue_ids.length == 0) {
            toastr.error("You must select at least one action from the queue.  Aborting Execution");
            return;
        }
        if (prompt("To execute all selected actions, type 'execute' in the space provied.  Note: This action cannot be undone!") != 'execute') {
            toastr.error("Aborting Execution");
            return;
        }
        toastr.info("Submitting Actions to Queue.  Please Wait...")
        ajax.post('/api/group_action_queue/execute',{ids:action_queue_ids},function(data) {
            toastr.success("All selected actions sent to Job Queue.  See Horizon for current status");
        },function(data) {
            // Do nothing!
        });
    });
});