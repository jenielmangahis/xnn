print <<EOS;

<link rel="stylesheet" href="$commission_engine_api_url/css/app.css?v=$app_css_version" />
<style>
    .hyperwallet__logo {
        margin: 0 auto;
    }
    .hyperwallet__invitation-link {
        font-weight: 900 !important;
    }
</style>

<div id="hyperwallet" class="hyperwallet tool-container tool-container--default" v-cloak>

    <div class="row">
        <div class="col-md-12">
            <img src="$commission_engine_api_url/images/hyperwallet.png" class="img-responsive hyperwallet__logo" draggable="false" alt="hyperwallet logo">
            <hr />
        </div>
    </div>

    <div class="row">

        <div class="col-md-4 col-md-offset-4">

            <div class="jumbotron" v-if="isRegistered">
                <p>Your account is registered. Activation email will be sent to your email address. After activation, you can log in to your Pay Portal by following <a :href="invitationLink" class="btn-link hyperwallet__invitation-link" target=”_blank”>this link</a>.</p>
            </div>
            <form v-on:submit.prevent="signUp">
                <div class="form-group">
                    <label for="user_id">Member ID</label>
                    <input :disabled="true"type="text" class="form-control"  :value="user.user_id" id="user_id" name="user_id">
                </div>
                <div class="form-group" :class="{ 'has-error': !!errors.first('first_name') }">
                    <label for="first_name">First Name</label>
                    <input :disabled="true" v-validate="{ required: true, max: 70}" type="text" class="form-control" v-model="user.first_name" id="first_name" name="first_name">
                    <span class="help-block">{{ errors.first('first_name') }}</span>
                </div>
                <div class="form-group" :class="{ 'has-error': !!errors.first('last_name') }">
                    <label for="last_name">Last Name</label>
                    <input :disabled="true" v-validate="{ required: true, max: 70 }" type="text" class="form-control" id="last_name" name="last_name" v-model="user.last_name">
                    <span class="help-block">{{ errors.first('last_name') }}</span>
                </div>
                <div class="form-group" :class="{ 'has-error': !!errors.first('email') }">
                    <label for="email" >Email <span v-if="!isRegistered" class="text-danger">*</span></label>
                    <input :disabled="isRegistered" v-validate="{ required: true, email: true, max: 70 }" type="text" class="form-control" id="email" name="email" v-model="user.email">
                    <span class="help-block">{{ errors.first('email') }}</span>
                </div>
                <div class="form-group" :class="{ 'has-error': !!errors.first('date_of_birth') }">
                    <label for="date_of_birth">Date of Birth <span v-if="!isRegistered" class="text-danger">*</span></label>
                    <input :disabled="isRegistered" v-validate="{ required: true, date_format: 'yyyy-MM-dd' }" type="text" class="form-control" id="date_of_birth" name="date_of_birth" v-model="user.date_of_birth">
                    <span class="help-block">{{ errors.first('date_of_birth') }}</span>
                </div>
                <div class="form-group" :class="{ 'has-error': !!errors.first('phone_number') }">
                    <label for="phone_number">Phone Number <span v-if="!isRegistered" class="text-danger">*</span></label>
                    <input :disabled="isRegistered" v-validate="{ required: true, max: 70 }" type="text" class="form-control" id="phone_number" name="phone_number" v-model="user.phone_number">
                    <span class="help-block">{{ errors.first('phone_number') }}</span>
                </div>
                <div class="form-group" :class="{ 'has-error': !!errors.first('country') }">
                    <label for="country">Country <span v-if="!isRegistered" class="text-danger">*</span></label>
                    <select :disabled="isRegistered" v-validate="{ required: true }" name="country" id="country" class="form-control" v-model="user.country" v-on:change="countryChange">
                        <option value="" selected disabled>Select a country</option>
                        <option v-for="country in countries" :value="country.code2">{{ country.name }}</option>
                    </select>
                    <span class="help-block">{{ errors.first('country') }}</span>
                </div>
                <div class="form-group" :class="{ 'has-error': !!errors.first('state') }">
                    <label for="state">State <span v-if="!isRegistered" class="text-danger">*</span></label>
                    <select :disabled="isRegistered" v-validate="{ required: true }" name="state" id="state" class="form-control" v-model="user.state_province">
                        <option value="" selected disabled>Select a state</option>
                        <option v-for="state in countryStates" :value="state.code">{{ state.name }}</option>
                    </select>
                    <span class="help-block">{{ errors.first('state') }}</span>
                </div>
                <div class="form-group" :class="{ 'has-error': !!errors.first('address_line_1') }">
                    <label for="address_line_1">Address Line 1 <span v-if="!isRegistered" class="text-danger">*</span></label>
                    <input :disabled="isRegistered" v-validate="{ required: true }" name="address_line_1" type="text" class="form-control" id="address_line_1" v-model="user.address_line_1">
                    <span class="help-block">{{ errors.first('address_line_1') }}</span>
                </div>
                <div class="form-group">
                    <label for="address_line_2">Address Line 2</label>
                    <input :disabled="isRegistered" type="text" class="form-control" id="address_line_2" v-model="user.address_line_2">
                </div>
                <div class="form-group" :class="{ 'has-error': !!errors.first('city') }">
                    <label for="city">City <span v-if="!isRegistered" class="text-danger">*</span></label>
                    <input :disabled="isRegistered" v-validate="{ required: true }" type="text" class="form-control" name="city" id="city" v-model="user.city">
                    <span class="help-block">{{ errors.first('city') }}</span>
                </div>
                <div class="form-group" :class="{ 'has-error': !!errors.first('postal_code') }">
                    <label for="postal_code">Postal Code <span v-if="!isRegistered" class="text-danger">*</span></label>
                    <input :disabled="isRegistered" v-validate="{ required: true }" type="text" class="form-control" id="postal_code" name="postal_code" v-model="user.postal_code">
                    <span class="help-block">{{ errors.first('postal_code') }}</span>
                </div>
                <button type="submit" class="btn btn-primary pull-right btn-lg" v-if="!isRegistered">Sign up</button>
            </form>
        </div>
    </div>

</div>

<script src="$commission_engine_api_url/js/app.js?v=$app_js_version"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/vee-validate/2.2.8/vee-validate.min.js"></script>
<script src="$commission_engine_api_url/js/affiliate_hyperwallet.js?v=1"></script>

EOS
1;