(function ($, api_url, Vue, swal, axios, location, undefined) {

    const client = commissionEngine.createAccessClient("member/pay-quicker");

    const vm = new Vue({
        el: '.tool-container',
        data() {
            return {
                isProcessing: 0,
                error: {
                    message: null,
                    type: null,
                },
                user: {
                    user_id: null,
                    first_name: null,
                    last_name: null,
                    email: null,
                    company_assigned_key: null,
                    has_plastic_card: 0,
                    invitation_key: null,
                    invitation_link: null,
                }
            };
        },
        mounted() {
            this.user.user_id = $('#member').val();
            this.getUser();
        },
        methods: {
            signUp() {

                if(this.isProcessing) return;

                swal({
                    title: "PayQuicker Pre-registration",
                    text: "Are you sure you want to create an account?",
                    type: "warning",
                    confirmButtonClass: "btn-success",
                    confirmButtonText: "Confirm",
                    cancelButtonText: "Cancel",
                    showCancelButton: true,
                    closeOnConfirm: false,
                    showLoaderOnConfirm: true,
                }, () => {

                    this.isProcessing = 1;

                    client.post("sign-up", this.user).then(response => {
                        this.error.message = null;
                        this.error.type = null;
                        this.user = response.data;
                        swal('Success','','success');
                    }).catch(this.axiosErrorHandler).finally(()=> {
                        this.isProcessing = 0;
                    });

                });
            },
            getUser() {
                client.get('users').then(response => {
                    this.user = response.data;
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
            isRegistered() {
                return !!this.user.invitation_key;
            },
            invitationLink() {
                return this.isRegistered ? this.user.invitation_link : `#`;
            },
        }
    });

}(jQuery, window.commissionEngine.API_URL, Vue, swal, axios, window.location));