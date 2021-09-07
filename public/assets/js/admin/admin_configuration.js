gform.options = {autoFocus:false};

$('#adminDataGrid').html(`
<div class="row">
    <div class="col-sm-12 default_username_template"></div>
</div>
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

ajax.get('/api/configuration/',function(data) {
    _.each(data,function(item) {
        if (_.has(gforms,item.name)) {
            gforms[item.name].set({config:item.config})
        }
    })
});
