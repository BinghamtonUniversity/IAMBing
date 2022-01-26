ajax.get('/api/configuration/',function(app_config) {
    affiliate_options = [{label:'No Affiliation',value:null}].concat(_.find(app_config,{name:'affiliations'}).config)
    var group_form_fields = [
        {type:"hidden", name:"id"},
        {type:"text", name:"name", label:"Name",required:true},
        {type:"text", name:"slug", label:"Slug",required:true},
        {type:"textarea", name:"description", label:"Description",required:false},
        {type:"select",label: "Affiliation",name:"affiliation",options:affiliate_options},
        {name:"type","label":"Type",type:"select",options:[{label:'Manually Managed',value:'manual'},{label:'Automatically Managed',value:'auto'}]},
    ];

    ajax.get('/api/groups',function(data) {
        gdg = new GrapheneDataGrid({el:'#adminDataGrid',
        item_template: gform.stencils['table_row'],
        search: false,columns: false,upload:false,download:false,title:'Groups',
        entries:[],
        sortBy: 'order',
        actions:actions,
        count:20,
        schema:group_form_fields, 
        data: data
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
            window.location = '/groups/'+grid_event.model.attributes.id+'/members';
        }).on("model:manage_entitlements",function(grid_event) {
            window.location = '/groups/'+grid_event.model.attributes.id+'/entitlements';
        }).on("model:manage_admins",function(grid_event) {
            window.location = '/groups/'+grid_event.model.attributes.id+'/admins';
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
                ajax.post('/api/groups/'+grid_event.model.attributes.id+'/identities/bulk_add',form_event.form.get(),function(data) {
                    if (data.added.length > 0 || data.ignored.length > 0 || data.skipped.length) {
                        template = `
                            {{#skipped.length}}
                                <div class="alert alert-danger">
                                    <h5>The Following IDs were ignored, as these identities do not exist within BComply:</h5>
                                    <ul>
                                    {{#skipped}}
                                        <li>{{.}}</li>
                                    {{/skipped}}
                                    </ul>
                                </div>
                            {{/skipped.length}}
                            {{#ignored.length}}
                                <div class="alert alert-info">
                                    <h5>The Following IDs were skipped, as these identities are already a member of this group:</h5>
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
        }).on('sort', e => {
            var tempdata = {items:_.map(e.grid.models, function(item){return item.attributes}).reverse()};//[].concat.apply([],pageData)
            var sortlist = '<ol id="sorter" class="list-group" style="margin: -15px;"> {{#items}} <li id="list_{{id}}" data-id="{{id}}" class="list-group-item filterable"> <div class="sortableContent"> <div class="fa fa-bars" style="cursor:move;"></div> {{name}} </div> </li> {{/items}} </ol>';
            var rendered_data = gform.m(sortlist,tempdata);
            mymodal = new gform({
                "legend":"Sort Groups (Affiliation Heirarchy)",
                "name": "sort_groups",
                "fields":[{type:"output",label:"",name:"output",format:{value:rendered_data}}]
            }).modal().on('save',function(e){
                var order = _.map($('#sorter').children(), (item,index) => {return {id:item.dataset.id,order:index}})
                ajax.put('/api/groups/order',{order:order},function(data) {
                    toastr.success('Updated Group Order');
                    e.form.trigger('close');
                },function(data) {
                    toastr.error('Failed to Update Group Order');
                });
            }).on('cancel',function(e){
                e.form.trigger('close');
            });
            Sortable.create($(mymodal.el).find('.modal-content ol')[0],{draggable:'li'});
        });
    ;
    });
});