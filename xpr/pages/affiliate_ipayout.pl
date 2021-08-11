print <<EOS;

<link rel="stylesheet" href="$commission_engine_api_url/css/app.css?v=$app_css_version" />
<style>
    .ipayout__logo {
        margin: 0 auto;
        width: 200px;
    }
    .ipayout__invitation-link {
        font-weight: 900 !important;
    }
</style>

<div id="ipayout" class="ipayout tool-container tool-container--default" v-cloak>

    <div class="row">
        <div class="col-md-12">
            <img src="$commission_engine_api_url/images/ipayout_logo.png" class="img-responsive ipayout__logo" draggable="false" alt="ipayout logo">
            <hr />
        </div>
    </div>

    <div class="row">

        <div class="col-md-4 col-md-offset-4">

            <div class="jumbotron" v-if="isRegistered">
                <p>Your account is registered. Activation email will be sent to your email address. After activation, you can log in to your Pay Portal by following <a :href="invitationLink" class="btn-link ipayout__invitation-link" target=”_blank”>this link</a>.</p>
            </div>
            <form v-on:submit.prevent="signUp">
                <fieldset>
                    <legend>Basic Info</legend>
                    <div class="form-group">
                        <label for="user_id">Member ID</label>
                        <input :disabled="true"type="text" class="form-control"  :value="user.user_id" id="user_id" name="user_id">
                    </div>
                    <div class="form-group" :class="{ 'has-error': !!errors.first('first_name') }">
                        <label for="first_name">First Name</label>
                        <input :disabled="true" v-validate="{ required: true, max: 50}" type="text" class="form-control" v-model="user.first_name" id="first_name" name="first_name">
                        <span class="help-block">{{ errors.first('first_name') }}</span>
                    </div>
                    <div class="form-group" :class="{ 'has-error': !!errors.first('last_name') }">
                        <label for="last_name">Last Name</label>
                        <input :disabled="true" v-validate="{ required: true, max: 50 }" type="text" class="form-control" id="last_name" name="last_name" v-model="user.last_name">
                        <span class="help-block">{{ errors.first('last_name') }}</span>
                    </div>
                    <div class="form-group" :class="{ 'has-error': !!errors.first('username') }">
                        <label for="username" >Username <span v-if="!isRegistered" class="text-danger">*</span></label>
                        <input :disabled="isRegistered" v-validate="{ required: true, max: 100 }" type="text" class="form-control" id="username" name="username" v-model="user.username">
                        <span class="help-block">{{ errors.first('username') }}</span>
                    </div>
                    <div class="form-group" :class="{ 'has-error': !!errors.first('email') }">
                        <label for="email" >Email <span v-if="!isRegistered" class="text-danger">*</span></label>
                        <input :disabled="isRegistered" v-validate="{ required: true, email: true, max: 100 }" type="text" class="form-control" id="email" name="email" v-model="user.email">
                        <span class="help-block">{{ errors.first('email') }}</span>
                    </div>
                    <div class="form-group" :class="{ 'has-error': !!errors.first('date_of_birth') }">
                        <label for="date_of_birth">Date of Birth <span v-if="!isRegistered" class="text-danger">*</span></label>
                        <input :disabled="isRegistered" v-validate="{ required: true, date_format: 'yyyy-MM-dd' }" type="text" class="form-control" id="date_of_birth" name="date_of_birth" v-model="user.date_of_birth">
                        <span class="help-block">{{ errors.first('date_of_birth') }}</span>
                    </div>
                    <div class="form-group" :class="{ 'has-error': !!errors.first('company_name') }">
                        <label for="company_name">Company Name</label>
                        <input :disabled="isRegistered" v-validate="{ required: false, max: 50 }" name="company_name" type="text" class="form-control" id="company_name" v-model="user.company_name">
                        <span class="help-block">{{ errors.first('company_name') }}</span>
                    </div>
                </fieldset>

                <fieldset>
                    <legend>Address Info</legend>

                    <div class="form-group" :class="{ 'has-error': !!errors.first('address_line_1') }">
                        <label for="address_1">Address 1</label>
                        <input :disabled="isRegistered" v-validate="{ required: false, max: 100 }" name="address_1" type="text" class="form-control" id="address_1" v-model="user.address_1">
                        <span class="help-block">{{ errors.first('address_1') }}</span>
                    </div>
                    <div class="form-group">
                        <label for="address_2">Address 2</label>
                        <input :disabled="isRegistered" type="text" class="form-control" id="address_2" v-model="user.address_2">
                    </div>

                    <div class="form-group" :class="{ 'has-error': !!errors.first('city') }">
                        <label for="city">City</label>
                        <input :disabled="isRegistered" v-validate="{ required: false, max: 30 }" type="text" class="form-control" name="city" id="city" v-model="user.city">
                        <span class="help-block">{{ errors.first('city') }}</span>
                    </div>

                    <div class="form-group" :class="{ 'has-error': !!errors.first('country_code') }">
                        <label for="country_code">Country </label>
                        <select :disabled="isRegistered" v-validate="{ required: false }" name="country_code" id="country_code" class="form-control" v-model="user.country_code" v-on:change="countryChange">
                            <option value="" selected disabled>Select a country</option>
                            <option v-for="country in countries" :value="country.code2">{{ country.name }}</option>
                        </select>
                        <span class="help-block">{{ errors.first('country_code') }}</span>
                    </div>

                    <div class="form-group" :class="{ 'has-error': !!errors.first('state') }">
                        <label for="state">State </label>
                        <select :disabled="isRegistered" v-validate="{ required: false }" name="state" id="state" class="form-control" v-model="user.state">
                            <option value="" selected disabled>Select a state</option>
                            <option v-for="state in countryStates" :value="state.code">{{ state.name }}</option>
                        </select>
                        <span class="help-block">{{ errors.first('state') }}</span>
                    </div>

                    <div class="form-group" :class="{ 'has-error': !!errors.first('zip_code') }">
                        <label for="zip_code">Postal Code</label>
                        <input :disabled="isRegistered" v-validate="{ required: false, max: 20 }" type="text" class="form-control" id="zip_code" name="zip_code" v-model="user.zip_code">
                        <span class="help-block">{{ errors.first('zip_code') }}</span>
                    </div>

                </fieldset>

                <button type="submit" class="btn btn-primary pull-right btn-lg" v-if="!isRegistered">Sign up</button>
            </form>
        </div>
    </div>

</div>

<script src="$commission_engine_api_url/js/app.js?v=$app_js_version"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/vee-validate/2.2.8/vee-validate.min.js"></script>
<script src="$commission_engine_api_url/js/affiliate_ipayout.js?v=1"></script>

EOS
1;