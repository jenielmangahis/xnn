print <<EOS;

<link rel="stylesheet" href="$commission_engine_api_url/css/app.css?v=$app_css_version" />
<style>
    .payeer__logo {
        margin: 0 auto;
    }
    .payeer__registration-link {
        font-weight: 900 !important;
    }
</style>

<div class="payeer tool-container tool-container--default" v-cloak>

    <div class="row">
        <div class="col-md-12">
            <img src="$commission_engine_api_url/images/payeer_logo.png" class="img-responsive payeer__logo" draggable="false" alt="payeer logo">
            <hr />
        </div>
    </div>

    <div class="row">
        <div class="col-md-4 col-md-offset-4">

            <div v-if="!isRegistered" class="jumbotron">
                <p>Your account is not yet registered. Create a Payeer account on <a href="https://payeer.com/en/auth/?register=yes" class="btn-link payeer__registration-link" target=”_blank”>this link</a>.</p>
            </div>

            <form v-on:submit.prevent="signUp">
                <div class="form-group">
                    <label for="user_id">Member ID</label>
                    <input :readonly="true" type="text"  class="form-control" id="user_id" name="user_id" v-model="user.user_id">
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
                <div class="form-group">
                    <label for="account_number">Account Number</label>
                    <input :readonly="isRegistered" type="text" class="form-control" id="account_number" name="account_number" v-model="user.account_number">
                </div>
                <button class="btn btn-primary" type="submit" v-if="!isRegistered">Save</button>
            </form>
        </div>
    </div>

</div>

<script src="$commission_engine_api_url/js/app.js?v=$app_js_version"></script>
<script src="$commission_engine_api_url/js/affiliate_payeer.js?v=1.2"></script>

EOS
1;