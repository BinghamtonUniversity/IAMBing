gform.options = {autoFocus:false};

$('#adminDataGrid').html(`
    <h3>Default Username Template</h3>
    <div class="alert alert-info">
        This is the mustache template for generating default usernames
    </div>
    <div class="default_username_template"></div>
    <h3>User Attributes</h3>
    <div class="alert alert-info">
        These are the user attributes which can be populated in IAMBing
    </div>
    <div class="user_attributes"></div>
    <h3>User Unique IDs</h3>
    <div class="alert alert-info">
        These are the unique user IDs which can be populated in IAMBing
    </div>
    <div class="user_unique_ids"></div>
    <h3>Affiliations</h3>
    <div class="alert alert-info">
        These are the various affiliations a person may have.  <a href="https://infrastructure.tamu.edu/directory/attribute/attribute_eduPersonAffiliation.html">More Info</a>
    </div>
    <div class="affiliations"></div>
    <h3>Database / Job Queue Reset</h3>
    <div class="alert alert-info">
    Refresh the IAMBing Database and/or Redis Job Queue! (Seriously guys, this is pretty serious)
    </div>
    <div class="btn btn-danger nuke_database">Reset Database</div>
    <div class="btn btn-danger nuke_redis">Flush Job Queue (Redis)</div>
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
        {type: "fieldset",label:'Attribute',columns:3,name: "config",array:{max:100},fields: 
            [{label: "Label",name: "label",},{label: "Name",name: "name"},{type:"checkbox", name:"array", label:"Multi-Value Attribute", value:false}]
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
        {type: "fieldset",label:'Unique ID',columns:3,name: "config",array:{max:100},fields: 
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

gforms.affiliations = new gform(
    {"fields":[
        {type:"hidden", name:"id"},
        {type:"hidden", name:"name", value:'affiliations'},
        {label: "Affiliation",name: "config", array:{max:100},columns:3}
	],
    "el":".affiliations",
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

$('.nuke_database').on('click',function() {
    toastr.warning('Database reset in progress...');
    ajax.get('/api/configuration/refresh/db',function(data) {
        toastr.success('Database has been reset!');
    });
});
$('.nuke_redis').on('click',function() {
    toastr.warning('Redis Job Queue flush in progress...');
    ajax.get('/api/configuration/refresh/redis',function(data) {
        toastr.success('Redis Job Queue has been reset!');
    });
});


ajax.get('/api/configuration/',function(data) {
    _.each(data,function(item) {
        if (_.has(gforms,item.name)) {
            gforms[item.name].set({config:item.config})
        }
    })
});
