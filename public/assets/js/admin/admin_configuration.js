gform.options = {autoFocus:false};

$('#adminDataGrid').html(`
    <div class="alert alert-info">
        This is the mustache template for generating default usernames
    </div>
    <div class="default_username_template"></div>
    
    <div class="alert alert-info">
        These are the user attributes which can be populated in IAMBing
    </div>
    <div class="user_attributes"></div>
    
    <div class="alert alert-info">
        These are the unique user IDs which can be populated in IAMBing
    </div>
    <div class="user_unique_ids"></div>
`);

var gforms = {};
gforms.default_username_template = new gform(
    {"fields":[
        {type:"hidden", name:"id"},
        {type:"hidden", name:"name", value:'default_username_template'},
        {type:"text", name:"config", label:"Default Username Template", edit:true},
    ],
    "el":".default_username_template",
    "actions":[
        {"type":"save","label":"Save","modifiers":"btn btn-primary"}
    ]
}
).on('save',function(e) {
    var form_data = e.form.get();
    ajax.put('/api/configuration/'+form_data.name,form_data,function(data) {
        toastr.success('Configuration Updated');
    });
});
gforms.user_attributes = new gform(
    {"fields":[
        {type:"hidden", name:"id"},
        {type:"hidden", name:"name", value:'user_attributes'},
        {type: "fieldset",label:'Attribute',columns:4,name: "config",array:{max:100},fields: 
            [{label: "Label",name: "label",},{label: "Name",name: "name"}]
    }],
    "el":".user_attributes",
    "actions":[
        {"type":"save","label":"Save","modifiers":"btn btn-primary"}
    ]
}
).on('save',function(e) {
    var form_data = e.form.get();
    ajax.put('/api/configuration/'+form_data.name,form_data,function(data) {
        toastr.success('Configuration Updated');
    });
});

gforms.user_unique_ids = new gform(
    {"fields":[
        {type:"hidden", name:"id"},
        {type:"hidden", name:"name", value:'user_unique_ids'},
        {type: "fieldset",label:'Unique ID',columns:4,name: "config",array:{max:100},fields: 
            [{label: "Label",name: "label",},{label: "Name",name: "name"}]
	    }],
    "el":".user_unique_ids",
    "actions":[
        {"type":"save","label":"Save","modifiers":"btn btn-primary"}
    ]
}
).on('save',function(e) {
    var form_data = e.form.get();
    ajax.put('/api/configuration/'+form_data.name,form_data,function(data) {
        toastr.success('Configuration Updated');
    });
});

ajax.get('/api/configuration/',function(data) {
    _.each(data,function(item) {
        if (_.has(gforms,item.name)) {
            gforms[item.name].set({config:item.config})
        }
    })
});
