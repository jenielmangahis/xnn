(function ($, api_url, Vue, swal, axios, location, undefined) {

    const client = commissionEngine.createAccessClient("member/payeer");

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
                    account_number: null,
                    is_registered: null,
                },
                temp_user_account_number: null,
                isEditMode: false
            };
        },
        mounted() {
            this.user.user_id = $('#member').val();
            this.getUser();
        },
        methods: {
            signUp() {

                if(this.isProcessing) return;

                if(!this.user.account_number) {
                    swal("Please Enter Account Number!");
                    return;
                }

                swal({
                    title: "Payeer Pre-registration",
                    text: "Are you sure you want to create an account?",
                    icon: "warning",
                    confirmButtonClass: "btn-success",
                    buttons: ["Cancel", "Confirm"],
                    closeModal: true,
                }).then((saveDetails) => {

                    if(saveDetails) {
                        this.isProcessing = 1;

                        client.post("create", this.user).then(response => {
                            this.error.message = null;
                            this.error.type = null;
                            this.user = response.data;
                            swal('Success','','success');
                        }).catch(this.axiosErrorHandler).finally(()=> {
                            this.isProcessing = 0;
                        });
                    }

                });

            },
            getUser() {
                client.get('users').then(response => {
                    this.user = response.data;
                    this.temp_user_account_number = this.user.account_number;
                }).catch(this.axiosErrorHandler);
            },
            editUser() {
                this.isEditMode = !this.isEditMode;
            },
            updateUser() {

                if(this.isProcessing) return;

                if(!this.user.account_number) {
                    swal("Please Enter Account Number!");
                    return;
                }
                
                if(this.temp_user_account_number == this.user.account_number) {
                    swal("The new account number is the same with the old account number!");
                    return;
                }

                swal({
                    title: "Payeer Update Account Number",
                    text: "Are you sure you want to update your account?",
                    icon: "warning",
                    confirmButtonClass: "btn-success",
                    buttons: ["Cancel", "Confirm"],
                    closeModal: true,
                }).then((updateDetails) => {

                    if(updateDetails) {
                        this.isProcessing = 1;

                        client.post("update", this.user).then(response => {
                            this.error.message = null;
                            this.error.type = null;

                            this.user = response.data;
                            this.temp_user_account_number = this.user.account_number;

                            this.isEditMode = false;
                            swal('Success','','success');
                        }).catch(this.axiosErrorHandler).finally(()=> {
                            this.isProcessing = 0;
                        });
                    }
                });
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
                return !!this.user.is_registered;
                // return false;
            },
        }
    });

}(jQuery, window.commissionEngine.API_URL, Vue, swal, axios, window.location));