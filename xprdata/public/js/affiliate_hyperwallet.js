(function ($, api_url, Vue, swal, axios, location, undefined) {

    const client = commissionEngine.createAccessClient();

    VeeValidate.Validator.localize({
        en: {
            messages:{
                required: () => 'This field is required.'
            },
            attributes: {
                first_name: "first name",
                last_name: "last name",
                email: "email",
                date_of_birth: "date of birth",
                country: "country",
                state: "state",
                address_line_1: "address line 1",
                city: "city",
                postal_code: "postal code",
            }
        }
    });

    Vue.use(VeeValidate);

    const vm = new Vue({
        el: '#hyperwallet',
        data() {
            return {
                is_processing: 0,
                error: {
                    message: null,
                    type: null,
                },
                countries: [],
                user: {
                    user_id: null,
                    first_name: null,
                    last_name: null,
                    client_user_id: null,
                    token: null,
                    status: null,
                    date_of_birth: null,
                    email: null,
                    address_line_1: null,
                    address_line_2: null,
                    city: null,
                    state_province: "",
                    country: "",
                    postal_code: null,
                    phone_number: "",
                }
            };
        },
        mounted() {
            this.getCountries(() => {
                this.getUser();
            });
        },
        methods: {
            signUp() {

                if(this.is_processing) return;

                this.$validator.validate().then(valid => {
                    if (!valid) {
                        return;
                    }

                    swal({
                        title: "Create hyperwallet account",
                        text: "Are you sure you want to create an account?",
                        type: "warning",
                        confirmButtonClass: "btn-success",
                        confirmButtonText: "Confirm",
                        cancelButtonText: "Cancel",
                        showCancelButton: true,
                        closeOnConfirm: false,
                        showLoaderOnConfirm: true,
                    }, () => {

                        this.is_processing = 1;

                        client.post(`member/hyperwallet/sign-up`, this.user).then(response => {
                            this.error.message = null;
                            this.error.type = null;
                            this.user = response.data;
                            swal('Success','','success');
                        }).catch(this.axiosErrorHandler).finally(()=> {
                            this.is_processing = 0;
                        });

                    });
                });
            },
            countryChange() {
                this.user.state_province = "";
            },
            getUser() {
                client.get('member/hyperwallet/users').then(response => {
                    this.user = response.data;
                }).catch(this.axiosErrorHandler);
            },
            getCountries(callback) {
                client.get('common/countries').then(response => {
                    this.countries = response.data;
                    typeof callback == "function" && callback();
                }).catch(this.axiosErrorHandler);
            },
            axiosErrorHandler(error) {
                let data = commissionEngine.parseAxiosErrorData(error.response.data);

                this.error.message = data.message;
                this.error.type = data.type;
                this.error.data = data.data;

                swal(this.error.message, "", "error");
            },
        },
        computed: {
            countryStates (){
                if(!this.user.country) return [];

                return this.countries.find(f => f.code2 === this.user.country).states;
            },
            isRegistered() {
                return !!this.user.token;
            },
            invitationLink() {
                return this.isRegistered ? this.user.invitation_link : `#`;
            },
        }
    });


}(jQuery, window.commissionEngine.API_URL, Vue, swal, axios, window.location));