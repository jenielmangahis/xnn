print <<EOS;

<link rel="stylesheet" href="$commission_engine_api_url/css/app.css?v=$app_css_version" />
<style>
    .payquicker__logo {
        margin: 0 auto;
    }
    .payquicker__invitation-link {
        font-weight: 900 !important;
    }
</style>

<div class="payquicker tool-container tool-container--default" v-cloak>

    <div class="row">
        <div class="col-md-12">
            <img src="$commission_engine_api_url/images/payquicker_logo.png" class="img-responsive payquicker__logo" draggable="false" alt="payquicker logo">
            <hr />
        </div>
    </div>

    <div class="row">

        <div class="col-md-4 col-md-offset-4">

            <div v-if="isRegistered" class="jumbotron">
                <p>Your account is registered. Activation email will be sent to your email address. After activation, you can log in to your Pay Portal by following <a :href="invitationLink" class="btn-link payquicker__invitation-link" target=”_blank”>this link</a>.</p>
            </div>

            <form v-on:submit.prevent="signUp">
                <div class="form-group">
                    <label for="company_assigned_key">Member ID</label>
                    <input :readonly="true" type="text"  class="form-control" id="company_assigned_key" name="company_assigned_key" v-model="user.company_assigned_key">
                </div>
                <div class="form-group">
                    <label for="first_name">First Name</label>
                    <input :readonly="true" type="text" class="form-control" id="first_name" name="first_name" v-model="user.first_name">
                </div>
                <div class="form-group">
                    <label for="last_name">Last Name</label>
                    <input :readonly="true" type="text" class="form-control" id="last_name" name="last_name" v-model="user.last_name">
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input :readonly="isRegistered" type="text" class="form-control" id="email" name="email" v-model="user.email">
                </div>
                <div class="checkbox hidden">
                    <label>
                        <input :readonly="isRegistered" :disabled="isRegistered" type="checkbox" v-model="user.has_plastic_card" true-value="1"
                               false-value="0"> Issue Plastic Card
                    </label>
                </div>
                <div class="form-group">
                    <label for="invitation_link">Invitation Link</label>
                    <input :readonly="true" type="text" v-model="invitationLink" class="form-control" id="invitation_link" name="invitation_link">
                </div>
                <button class="btn btn-primary" type="submit" v-if="!isRegistered">Submit</button>
            </form>
        </div>
    </div>

</div>

<script src="$commission_engine_api_url/js/app.js?v=$app_js_version"></script>
<script src="$commission_engine_api_url/js/affiliate_payquicker.js?v=1"></script>

EOS
1;