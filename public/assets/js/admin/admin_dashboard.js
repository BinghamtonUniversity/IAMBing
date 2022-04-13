dashboard_template = `
<h1>Welcome {{first_name}} {{last_name}}</h1>
<div class="row">
    <div class="col-sm-6">
        <div class="panel panel-default">
            <div class="panel-heading"><h3 class="panel-title">My Groups</h3></div>
            <div class="panel-body">
                {{#groups}}
                    <li>{{name}}</li>
                {{/groups}}
                {{^groups}}
                    <div class="alert alert-warning">No Group Memberships</div>
                {{/groups}}
            </div>
        </div>
        <div class="panel panel-default">
            <div class="panel-heading"><h3 class="panel-title">My Accounts</h3></div>
            <div class="panel-body">
                <ul>
                    {{#systems}}
                        <li>{{pivot.username}} ({{name}})</li>
                    {{/systems}}
                </ul>
                {{^systems}}
                    <div class="alert alert-warning">No Accounts</div>
                {{/systems}}
            </div>
        </div>
    </div>
    <div class="col-sm-12">
        <div class='row'>
        <div class='col-sm-6'>
            <div class="panel panel-default">
                <div class="panel-heading"><h3 class="panel-title">My Entitlements</h3></div>
                <div class="panel-body">
                    <div class='table table-responsive'>
                        <table class='table table-bordered'>
                            <thead>
                                <tr>
                                    <th scope='col' class='text-center'>Name</th>
                                    <th scope='col' class='text-center'>Sponsor</th>
                                    <th scope='col' class='text-center'>Expiration Date</th>
                                </tr>
                            </thead>
                            <tbody class='text-center'>
                                {{#identity_entitlements}}
                                    <tr scope='row'>
                                        <td>{{name}}</td>
                                        <td>{{pivot.sponsor_id}}</td>
                                        <td>{{pivot.expiration_date}}</td>
                                    </tr>
                                {{/identity_entitlements}}
                            </tbody>
                        </table>
                    </div>
                    
                    {{^identity_entitlements}}
                        <div class="alert alert-warning">No Entitlements</div>
                    {{/identity_entitlements}}
                </div>
        </div>
        </div>
        <div class='col-sm-6'>
            <div class="panel panel-default">
                <div class="panel-heading"><h3 class="panel-title">Sponsored Entitlements</h3></div>
                <div class="panel-body">
                    <div class='table table-responsive'>
                        <table class='table table-bordered'>
                            <thead>
                                <tr>
                                    <th></th>
                                    <th scope='col' class='text-center'>Description</th>
                                    <th scope='col' class='text-center'>Identity Name</th>
                                    <th scope='col' class='text-center'>Identity Default Email</th>
                                    <th scope='col' class='text-center'>Allowed Renewal Days</th>
                                    <th scope='col' class='text-center'>Expiration Date</th>
                                    <th scope='col' class='text-center'>Renewal Allowed</th>
                                </tr>
                            </thead>
                            <tbody class='text-center'>
                                {{#sponsored_entitlements}}
                                    <tr scope='row'>
                                        <td><input type="checkbox" class="form-check-input" name="ent_id" data-id="{{id}}"></td>
                                        <td>{{description}}</td>
                                        <td>{{identity.first_name}} {{identity.last_name}}</td>
                                        <td>{{identity.default_email}}</td>
                                        <td>{{sponsor_renew_days}}</td>
                                        <td>{{expiration_date}}</td>
                                        <td>{{sponsor_renew_allow}}</td>
                                    </tr>
                                {{/sponsored_entitlements}}
                            </tbody>
                        </table>
                    </div>
                    <div id="renewButton" onclick="renewFunc()" class="btn btn-primary">Renew</div>
                </div>
            </div>
        </div>
        </div>
    </div>
</div>
`;

ajax.get('/api/identities/'+id,function(data) {
    window.dashboard_data = data;
    $('#adminDataGrid').html(gform.m(dashboard_template,window.dashboard_data));
});

function renewFunc(e){
    var renew_ids = Array.from(document.getElementsByName('ent_id')).filter( e => e.checked===true).map( e=>e.dataset.id);  
    if(renew_ids.length==0){
        toastr.error("Please select a user to renew!");
    }else{
        ajax.post("/api/entitlements/renew",{entitlements:renew_ids},function(resp){
            window.dashboard_data.sponsored_entitlements = window.dashboard_data.sponsored_entitlements.map(c => {
                temp = resp.find(d => d.id ==c.id);
                if (temp){
                    return temp;
                }
                return c;
            });
            // debugger;
            $('#adminDataGrid').html(gform.m(dashboard_template,window.dashboard_data));
            toastr.success("Renewal Successfull!");
        });
        
    }
    

}