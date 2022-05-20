dashboard_template = `
<h1>Welcome {{first_name}} {{last_name}}</h1>
<div class="row">
    <div class="col-sm-6">
        <!-- Start Column 1 -->
        <!-- My Accounts -->
        <div class="panel panel-default">
            <div class="panel-heading"><h3 class="panel-title">My Accounts</h3></div>
            <div class="panel-body">
                <ul>
                    {{#systems}}
                        <li>{{pivot.account_id}} ({{name}})</li>
                    {{/systems}}
                </ul>
                {{^systems}}
                    <div class="alert alert-warning">No Accounts</div>
                {{/systems}}
            </div>
        </div>
        <!-- My Affiliations -->
        <div class="panel panel-default">
            <div class="panel-heading"><h3 class="panel-title">My Affiliations</h3></div>
            <div class="panel-body">
                {{#affiliations}}
                    <li>{{.}}</li>
                {{/affiliations}}
                {{^affiliations}}
                    <div class="alert alert-warning">No Affiliations</div>
                {{/affiliations}}
            </div>
        </div>
        <!-- My Groups -->
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
        <!-- End Column 1 -->
    </div>
    <div class='col-sm-6'>
        <!-- Start Column 2 -->
        <!-- My Normal Entitlements -->
        <div class="panel panel-default">
            <div class="panel-heading"><h3 class="panel-title">My Non-Sponsored Entitlements</h3></div>
            <div class="panel-body">
                <ul>
                    <div class='row'>
                        {{#identity_entitlements}}
                            {{^pivot.sponsor_id}}<div class='col-sm-6'><li>{{name}}</li></div>{{/pivot.sponsor_id}}
                        {{/identity_entitlements}}
                    </div>
                </ul>
                {{^identity_entitlements}}
                    <div class="alert alert-warning">No Accounts</div>
                {{/identity_entitlements}}
            </div>
        </div>

        <!-- My Sponsored Entitlements -->
        <div class="panel panel-default">
            <div class="panel-heading"><h3 class="panel-title">My Sponsored Entitlements</h3></div>
                <div class="panel-body">
                    <div class='table table-responsive'>
                        <table class='table table-bordered'>
                            <thead>
                                <tr>
                                    <th scope='col' class='text-center'>Name</th>
                                    <th scope='col' class='text-center'>Sponsor</th>
                                    <th scope='col' class='text-center'>Sponsor Email</th>
                                    <th scope='col' class='text-center'>Expiration Date</th>
                                </tr>
                            </thead>
                            <tbody class='text-center'>
                                {{#identity_entitlements_with_sponsors}}
                                    <tr scope='row'>
                                        <td>{{entitlement.name}}</td>
                                        <td>{{sponsor.first_name}} {{sponsor.last_name}}</td>
                                        <td>{{sponsor.default_email}}</td>
                                        <td>{{expiration_date}}</td>
                                    </tr>
                                {{/identity_entitlements_with_sponsors}}
                            </tbody>
                        </table>
                    </div>
                    {{^identity_entitlements}}
                        <div class="alert alert-warning">No Entitlements</div>
                    {{/identity_entitlements}}
                </div>
            </div>

        <!-- Entitlements I Sponsor -->
        <div class="panel panel-default">
            <div class="panel-heading"><h3 class="panel-title">Identity Entitlements Sponsored by Me</h3></div>
            <div class="panel-body">
                <div class='table table-responsive'>
                    <table class='table table-bordered'>
                        <thead>
                            <tr>
                                <th></th>
                                <th scope='col' class='text-center'>Entitlement</th>
                                <th scope='col' class='text-center'>Name</th>
                                <th scope='col' class='text-center'>Email</th>
                                <th scope='col' class='text-center'>Renewal Days</th>
                                <th scope='col' class='text-center'>Expiration Date</th>
                                <th scope='col' class='text-center'>Renewal Allowed</th>
                            </tr>
                        </thead>
                        <tbody class='text-center'>
                            {{#sponsored_entitlements}}
                                <tr scope='row'>
                                    <td>{{#sponsor_renew_allow}}
                                    <input type="checkbox" class="form-check-input" name="ent_id" data-id="{{id}}">
                                    {{/sponsor_renew_allow}}</td>
                                    <td>{{entitlement.name}}</td>
                                    <td>{{identity.first_name}} {{identity.last_name}}</td>
                                    <td>{{identity.default_email}}</td>
                                    <td>{{sponsor_renew_days}}</td>
                                    <td>{{expiration_date}}</td>
                                    <td>{{#sponsor_renew_allow}}Allowed{{/sponsor_renew_allow}}{{^sponsor_renew_allow}}Not Allowed{{/sponsor_renew_allow}}</td>
                                </tr>
                            {{/sponsored_entitlements}}
                        </tbody>
                    </table>
                </div>
                <div id="renewButton" onclick="renewFunc()" class="btn btn-primary">Renew</div>
            </div>
        </div>
        <!-- End Column 2 -->
    </div>
</div>
`;
var dashboard_data = {}
ajax.get('/api/identities/'+id+'/dashboard',function(data) {
    dashboard_data = data;
    $('#adminDataGrid').html(gform.m(dashboard_template,dashboard_data));
});

function renewFunc(e){
    var renew_ids = Array.from(document.getElementsByName('ent_id')).filter( e => e.checked===true).map( e=>e.dataset.id);  
    if(renew_ids.length==0){
        toastr.error("Please select a user to renew!");
    }else{
        ajax.post("/api/entitlements/renew",{entitlements:renew_ids},function(resp){
            dashboard_data.sponsored_entitlements = dashboard_data.sponsored_entitlements.map(c => {
                temp = resp.find(d => d.id ==c.id);
                if (temp){
                    return temp;
                }
                return c;
            });
            $('#adminDataGrid').html(gform.m(dashboard_template,dashboard_data));
            toastr.success("Renewal Successfull!");
        });
        
    }
    

}