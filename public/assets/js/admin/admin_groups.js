ajax.get('/api/groups',function(data) {
    gdg = new GrapheneDataGrid({el:'#adminDataGrid',
    item_template: gform.stencils['table_row'],
    search: false,columns: false,upload:false,download:false,title:'Groups',
    entries:[],
    actions:[
        {"name":"create","label":"New Group"},
        '',
        {"name":"edit","label":"Update Group"},
        {"label":"Manage Members","name":"manage_members","min":1,"max":1,"type":"default"},
        {"label":"Manage Entitlements","name":"manage_entitlements","min":1,"max":1,"type":"default"},
        {"label":"Manage Administrators","name":"manage_admins","min":1,"max":1,"type":"default"},
        '',
        {"name":"delete","label":"Delete Group"}
    ],
    count:20,
    schema:[
        {type:"hidden", name:"id"},
        {type:"text", name:"name", label:"Name",required:true},
        {type:"textarea", name:"description", label:"Description",required:true},
        {type:"user", name:"user_id",required:true, label:"Group Owner", template:"{{#attributes.owner}}{{attributes.owner.first_name}} {{attributes.owner.last_name}}{{/attributes.owner}}"},
    ], data: data
    }).on("model:edited",function(grid_event) {
        ajax.put('/api/groups/'+grid_event.model.attributes.id,grid_event.model.attributes,function(data) {
            grid_event.model.update(data)
        },function(data) {
            grid_event.model.undo();
        });
    }).on("model:created",function(grid_event) {
        ajax.post('/api/groups',grid_event.model.attributes,function(data) {
            grid_event.model.update(data)
        },function(data) {
            grid_event.model.undo();
        });
    }).on("model:deleted",function(grid_event) {
        ajax.delete('/api/groups/'+grid_event.model.attributes.id,{},function(data) {},function(data) {
            grid_event.model.undo();
        });
    }).on("model:manage_members",function(grid_event) {
        window.location = '/admin/groups/'+grid_event.model.attributes.id+'/members';
    }).on("model:manage_entitlements",function(grid_event) {
        window.location = '/admin/groups/'+grid_event.model.attributes.id+'/entitlements';
    }).on("model:manage_admins",function(grid_event) {
        window.location = '/admin/groups/'+grid_event.model.attributes.id+'/admins';
    }).on('model:bulk_add',function(grid_event){

        new gform({
            "legend":"Bulk Add",
            "name": "bulk_add",
            "fields": [
                {
                    "type": "textarea",
                    "label": "Unique IDs",
                    "name": "unique_ids",
                    "showColumn": true,
                    "help":"Please enter a list of Unique IDs (BNumbers). " +
                        "You can either use a \",\" (comma) to separate them or enter them in separate lines<br>" +
                        "Duplicates or existing group members will be ignored."
                }
            ]
        }).on('save',function(form_event){
            toastr.info('Processing... Please Wait')
            form_event.form.trigger('close');
            ajax.post('/api/groups/'+grid_event.model.attributes.id+'/users/bulk_add',form_event.form.get(),function(data) {
                if (data.added.length > 0 || data.ignored.length > 0 || data.skipped.length) {
                    template = `
                        {{#skipped.length}}
                            <div class="alert alert-danger">
                                <h5>The Following IDs were ignored, as these users do not exist within BComply:</h5>
                                <ul>
                                {{#skipped}}
                                    <li>{{.}}</li>
                                {{/skipped}}
                                </ul>
                            </div>
                        {{/skipped.length}}
                        {{#ignored.length}}
                            <div class="alert alert-info">
                                <h5>The Following IDs were skipped, as these users are already a member of this group:</h5>
                                <ul>
                                {{#ignored}}
                                    <li>{{.}}</li>
                                {{/ignored}}
                                </ul>
                            </div>
                        {{/ignored.length}}
                        {{#added.length}}
                            <div class="alert alert-success">
                                <h5>The Following IDs were sucessfully added:</h5>
                                <ul>
                                {{#added}}
                                    <li>{{.}}</li>
                                {{/added}}
                                </ul>
                            </div>
                        {{/added.length}}
                        `;
                    $('#adminModal .modal-title').html('Additional Information')
                    $('#adminModal .modal-body').html(gform.m(template,data));
                    $('#adminModal').modal('show')
                }
            },function(data){
            });
        }).on('cancel',function(form_event){
            form_event.form.trigger('close');
        }).modal()
    });
});

// Built-In Events:
//'edit','model:edit','model:edited','model:create','model:created','model:delete','model:deleted'


