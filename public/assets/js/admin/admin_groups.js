ajax.get('/api/configuration/',function(app_config) {
    affiliate_options = [{label:'No Affiliation',value:null}].concat(_.find(app_config,{name:'affiliations'}).config)
    var group_form_fields = [
        {type:"hidden", name:"id"},
        {type:"text", name:"name", label:"Name",required:true},
        {type:"text", name:"slug", label:"Slug",required:true},
        {type:"textarea", name:"description", label:"Description",required:false},
        {type:"select",label: "Affiliation",name:"affiliation",options:affiliate_options},
        {name:"type",label:"Type",type:"select",options:[{label:'Manually Managed',value:'manual'},{label:'Automatically Managed',value:'auto'}]},
        {name:"manual_confirmation_add",label:"Confirm Add",type:"switch",options:[{label:'Disabled',value:false},{label:'Enabled',value:false}],show:[{type:'matches',name:'type',value:'auto'}],parse:'show',help:'Enable this option if you want to manually confirm whenever a user is ADDED to this group.  (Will be added to "Action Queue")'},
        {name:"manual_confirmation_remove",label:"Confirm Remove",type:"switch",options:[{label:'Disabled',value:false},{label:'Enabled',value:false}],show:[{type:'matches',name:'type',value:'auto'}],parse:'show',help:'Enable this option if you want to manually confirm whenever a user is REMOVED from this group.  (Will be added to "Action Queue")'},
    ];

    ajax.get('/api/groups',function(data) {
        gdg = new GrapheneDataGrid({el:'#adminDataGrid',
        name: 'groups',
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