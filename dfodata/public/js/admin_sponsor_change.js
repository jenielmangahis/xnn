(function ($, api_url, Vue, swal, axios, location, undefined) {

    const client = commissionEngine.createAccessClient();
    commissionEngine.setupAccessTokenJQueryAjax();

    const vm = new Vue({
        el: "#sponsor-change",
        data() {
            return {
                tree_id: "1",
                member_id: null,
                sponsor_id: null,
                selected_tree_id: null,
                selected_member_id: null,
                selected_sponsor_id: null,
                dtHistory: null,
                error: {
                    message: null,
                },
                is_processing: 0,
                relationship: {
                    before: [],
                    after:[],
                },
                autocompleteUrl: `${api_url}common/autocomplete/members`,
            }
        },
        mounted() {
            this.initializeDatatable();
        },
        methods: {
            initializeDatatable() {

                this.dtHistory = $("#table-history").DataTable({
                    language: {
                        search: "_INPUT_",
                        searchPlaceholder: "Search",
                        paginate: {
                            next: 'Next',
                            previous: 'Previous &nbsp;&nbsp;&nbsp;|'
                        }
                    },
                    responsive: true
                });
            },
            clear() {
                this.relationship.before = [];
                this.relationship.after = [];
                this.error.message = null;
                this.selected_tree_id = null;
                this.selected_member_id = null;
                this.selected_sponsor_id = null;
            },
            viewDetails() {

                if(this.is_processing == 1) return;

                this.clear();

                if(!this.tree_id) {
                    this.error.message = "Tree is required";
                    return;
                }

                if(!this.member_id) {
                    this.error.message = "Member is required";
                    return;
                }

                if(!this.sponsor_id) {
                    this.error.message = "Sponsor is required";
                    return;
                }

                this.is_processing = 1;

                client.get(`admin/sponsor-change/relationship?tree_id=${this.tree_id}&member_id=${this.member_id}&sponsor_id=${this.sponsor_id}`).then(response => {

                    console.log(response);
                    this.relationship.before = response.data.before;
                    this.relationship.after = response.data.after;
                    this.error.message = null;
                    this.is_processing = 0;

                    this.selected_tree_id = this.tree_id;
                    this.selected_member_id = this.member_id;
                    this.selected_sponsor_id = this.sponsor_id;

                }).catch(this.axiosErrorHandler).finally(()=> {

                    // let result = xhr.responseJSON;
                    // this.error.message = result.error.message;
                    this.is_processing = 0;

                });

            },
            changeSponsor() {
                if(this.is_processing === 1) return;

                swal({
                    title: "Change Sponsor",
                    text:
                        "Are you sure you'd like to change the sponsor of the selected member. \n" +
                        "Do you want to continue?",
                    type: "warning",
                    confirmButtonClass: "btn-success",
                    confirmButtonText: "Yes!",
                    cancelButtonText: "No, cancel please!",
                    showCancelButton: true,
                    closeOnConfirm: false,
                    showLoaderOnConfirm: true,
                }, () => {
                    this.is_processing = 1;

                    client.post(`admin/sponsor-change/change`, {
                        tree_id: this.selected_tree_id,
                        member_id: this.selected_member_id,
                        sponsor_id: this.selected_sponsor_id,
                        moved_by_id: $('#member').val(),
                    }).then(response => {

                        this.error.message = null;
                        this.is_processing = 0;

                        swal({
                            title: "Success!",
                            text: "Successfully changed the sponsor",
                            type: "success",
                        });

                        this.member_id = null;
                        this.sponsor_id = null;
                        this.clear();
                        this.dtHistory.draw();

                    }).catch(this.axiosErrorHandler).finally(()=> {
                        
                        this.is_processing = 0;

                    });


                });
            },
            axiosErrorHandler(error) {

                let data = commissionEngine.parseAxiosErrorData(error.response.data);

                this.error.message = data.message;
                this.error.type = data.type;
                this.error.data = data.data;

                swal(this.error.message, "", "error");
            },
        }
    });

}(jQuery, window.commissionEngine.API_URL, Vue, swal, axios, window.location));