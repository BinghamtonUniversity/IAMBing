var mymodal = new gform(
    {"fields":[
        {name:'output',value:'',type:'output',label:''}
    ],
    "title":"Info",
    "actions":[]}
);

ajax.get('/api/groups/'+id+'/members',function(data) {
    gdg = new GrapheneDataGrid({el:'#adminDataGrid',
    name: 'groups_members',
    search: false,columns: false,upload:false,download:false,title:'Identities',
    entries:[],
    actions:actions,
    count:20,
    schema:[
        {type:"hidden", name:"id"},
        {name:"name", label:"Identity"},
    ], data: data
    }).on("add",function(grid_event) {
        var bulk_add_modal = new gform(
            {"fields":[
                {type:"identity", name:"id",required:true, label:"Identity"},
            ],
            "title":"Add Member",
            "actions":[
                {"type":"cancel"},
                {"type":"button","label":"Submit","action":"save","modifiers":"btn btn-success"},
            ]}
        ).modal().on('save',function(event) {
            var data = event.form.get();
            ajax.post('/api/groups/'+id+'/members/'+data.id,{},function(data) {
                grid_event.grid.add(data);
                event.form.trigger('close');
            },function(data) {
                // Do Nothing
            });
        }).on('cancel',function(event) {
            event.form.trigger('close');
        });
    }).on("model:deleted",function(grid_event) {
        ajax.delete('/api/groups/'+id+'/members/'+grid_event.model.attributes.id,{},
            function(data) {},
            function(data) {
            grid_event.model.undo();
        });
    }).on("bulk_add",function(grid_event) {
        var bulk_add_modal = new gform(
            {"fields":[
                {name:'unique_id_type',type:'select',label:'Unique ID Type',options:'/api/configuration/identity_unique_ids',"format":{label:"{{label}}",value:"{{name}}"}},
                {name:'unique_ids',value:'',type:'textarea',label:'Unique IDs'}
            ],
            "title":"Bulk Add Members",
            "actions":[
                {"type":"cancel"},
                {"type":"button","label":"Submit","action":"save","modifiers":"btn btn-warning"},
            ]}
        ).modal().on('save',function(event) {
            var data = event.form.get();
            ajax.post('/api/groups/'+id+'/members_bulk/'+data.unique_id_type,{unique_ids:data.unique_ids},
            function(data) {
                if (data.identities.length > 0) {
                    toastr.success('Sucessfully submitted '+data.identities.length+' identities to be added. (Check Horizon / Jobs for current status)');
                    event.form.trigger('close');
                } else {
                    toastr.warning('No identities found')
                }
            },function(data) {
                toastr.error('There was an error!');
            });
        }).on('cancel',function(event) {
            event.form.trigger('close');
        });
    }).on("bulk_remove",function(grid_event) {
        var bulk_remove_modal = new gform(
            {"fields":[
                {name:'unique_id_type',type:'select',label:'Unique ID Type',options:'/api/configuration/identity_unique_ids',"format":{label:"{{label}}",value:"{{name}}"}},
                {name:'unique_ids',value:'',type:'textarea',label:'Unique IDs'}
            ],
            "title":"Bulk Remove Members",
            "actions":[
                {"type":"cancel"},
                {"type":"button","label":"Submit","action":"save","modifiers":"btn btn-warning"},
            ]}
        ).modal().on('save',function(event) {
            var data = event.form.get();
            ajax.delete('/api/groups/'+id+'/members_bulk/'+data.unique_id_type,{unique_ids:data.unique_ids},
            function(data) {
                if (data.identities.length > 0) {
                    toastr.success('Sucessfully submitted '+data.identities.length+' identities to be removed. (Check Horizon / Jobs for current status)');
                    event.form.trigger('close');
                } else {
                    toastr.warning('No identities found')
                }
            },function(data) {
                toastr.error('There was an error!');
            });
        }).on('cancel',function(event) {
            event.form.trigger('close');
        });
    }).on('click',function(event) {
        window.location = '/identities/'+event.model.attributes.id
    });
});

// Built-In Events:
//'edit','model:edit','model:edited','model:create','model:created','model:delete','model:deleted'


