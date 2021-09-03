(function ($, api_url, Vue, swal, axios, location, undefined) {

    const client = commissionEngine.createAccessClient(`member/binary-tree`);
    commissionEngine.setupAccessTokenJQueryAjax();

    let tree = null;
    const vm = new Vue({
        el: "#binary-tree",
        data: {
            is_fetching: 0,
            tree: null,
            breadcrumb: [],
            root_id: null,
            owner_id: null,
            parent_id: null,
            hasUnplacedMember: null,
            autocomplete_url: `${api_url}common/autocomplete/binary-downline`,
            downline_id: null,
            member: {
                user_id: null,
                parent_id: null,
                carry_over_volume_left: null,
                current_group_volume_left: null,
                total_group_volume_left: null,
                carry_over_volume_right: null,
                current_group_volume_right: null,
                total_group_volume_right: null,
                placement_preference: null,
            },
            placement_preference: null,
            is_fetching_user: 0,
            is_updating_preference: 0,
        },
        mounted() {
            this.root_id = $('#member').val();
            this.owner_id = $('#member').val();
            this.getUserDetails();
            this.loadMatrixTree(this.root_id);
            this.initializeJQueryEvents();
        },
        methods: {
            initializeJQueryEvents() {
                let _this = this;

                $('#chart-container').on('click', '.node', function () {
                    let node = $(this).data('nodeData');

                    if(+node.is_empty) return;

                    _this.loadMatrixTree(node.user_id);
                });
            },
            loadMatrixTree(user_id) {
                if (+user_id === +this.parent_id) return;

                this.getDownline(user_id, (downline, breadcrumb) => {
                    this.parent_id = user_id;
                    this.breadcrumb = breadcrumb;

                    let tree_config = {
                        data: downline,
                        nodeId: "user_id",
                        nodeContent: 'name',
                        nodeTitle: "user_id",
                        toggleSiblingsResp: false,
                        parentNodeSymbol: null,
                        nodeTemplate(data) {

                            let template = '';

                            let position = +data.position ? "Right" : "Left";

                            if(+data.is_empty) {
                                template = `
                                    <div class="title">Empty</div>
                                    <div class="content" style="display: block;">
                                        <p class="content-name">Empty (${position})</p>
                                        <span class="content-current-rank"></span>
                                    </div>
                                `;
                            } else {
                                template = `
                                    <div class="title">#${data.user_id}</div>
                                    <div class="content" style="display: block;">
                                        <p class="content-name">${data.member}</p>
                                        <span class="content-current-rank">Rank: <strong>${data.paid_as_rank}</strong></span>
                                    </div>
                                `;
                            }

                            return template;
                        },
                        initCompleted() {
                            vm.initializeTooltip();
                        },
                    };

                    if (tree !== null) {
                        tree.init(tree_config);
                    } else {
                        tree = $('#chart-container').orgchart(tree_config);
                    }

                    let outerContent = $('#chart-container');
                    let innerContent = $('#chart-container > .orgchart');
                    outerContent.scrollLeft((innerContent.width() - outerContent.width()) / 2)
                });
            },
            getUserDetails() {

                if (this.is_fetching_user === 1) return;

                this.is_fetching_user = 1;

                client.get("user-details")
                    .then(response => {
                        this.member = response.data;

                        this.placement_preference = this.member.placement_preference;
                    }).catch(error => {
                        if(+error.response.status === 404) {
                            swal("You are not on the binary tree.", "... or please try again after 5 minutes.", "error");
                        }
                    }).finally(() => {
                        this.is_fetching_user = 0;
                    });
            },
            updatePlacementPreference() {
                if(this.is_updating_preference === 1) return;

                this.is_updating_preference = 1;

                client.patch('user/placement-preference', {
                    placement_preference: this.placement_preference,
                }).then(response => {
                    this.member.placement_preference = response.data.placement_preference;
                    this.placement_preference = this.member.placement_preference;
                }).catch(error => {
                    this.placement_preference = this.member.placement_preference;
                }).finally(() => {
                    this.is_updating_preference = 0;
                });
            },
            getDownline(user_id, callback) {

                if (this.is_fetching === 1) return;

                this.is_fetching = 1;

                client.get(`${this.root_id}/downline/${user_id}`).then(response => {
                    this.is_fetching = 0;
                    let data = response.data.downline;
                    const root = [];
                    const map = {};
                    let paidAsRankIdClassName;

                    data.forEach(node => {
                        
                        if(node.paid_as_rank_id == null || node.paid_as_rank_id == 0) {
                            paidAsRankIdClassName = "ranktype_customer";
                        }else {
                            paidAsRankIdClassName = "ranktype_"+node.paid_as_rank_id;
                        }
                        
                        if (+node.is_empty === 1) {
                            node.className = "node-empty "+paidAsRankIdClassName;
                        } else if (+node.is_active) {
                            node.className = "node-active "+paidAsRankIdClassName;
                        } else {
                            node.className = "node-inactive "+paidAsRankIdClassName;
                        }

                        if (+node.user_id === +user_id) return root.push(node);

                        let parentIndex = map[node.parent_id];
                        if (typeof parentIndex !== "number") {
                            parentIndex = data.findIndex(el => el.user_id === node.parent_id);
                            map[node.parent_id] = parentIndex;
                        }

                        if (parentIndex < 0) {
                            console.log(node);
                            return true;
                        }

                        if (!data[parentIndex].children) {
                            return data[parentIndex].children = [node];
                        }

                        data[parentIndex].children.push(node);
                    });

                    callback(root[0], response.data.breadcrumb);
                }).catch(error => {
                    this.is_fetching = 0;
                    console.log(error);
                    callback({}, []);
                });
            },
            selectionChange(value) {

                if (!!value) {
                    this.root_id = value;
                } else {
                    this.root_id = this.owner_id;
                }

                this.loadMatrixTree(this.root_id);
            },
            initializeTooltip() {
                tippy('.orgchart .node', {
                    placement: 'right',
                    content: 'Loading...',
                    allowHTML: true,
                    theme: 'light',
                    followCursor: true,
                    onCreate(instance) {
                        // Setup our own custom state properties
                        instance._isFetching = false;
                        instance._rankDetails = null;
                        instance._error = null;
                        instance._nodeData = null;
                    },
                    onShow(instance) {
                        if (instance._isFetching || instance._rankDetails || instance._error) {
                            return;
                        }

                        let data = instance._nodeData = $(instance.reference).closest('.node').data('nodeData');

                        if(+data.is_empty) {
                            instance.setContent(`<p>Empty</p>`);

                            return;
                        }

                        let content = `
                            <p>Name: <span>${ data.member }</span></p>
                            <p>Enrollment Sponsor: <span>${ data.sponsor }</span></p>
                            <p>Enrollment Date: <span>${ data.enrolled_date }</span></p>
                            <p>Paid-as Rank: <span>${ data.paid_as_rank }</span></p>
                            <p>Personal Volume: <span>${ data.pv }</span></p>
                            <p>Username: <span>${ data.site }</span></p>
                        `;

                        instance.setContent(content);

                    },
                    onHidden(instance) {

                        if (instance._rankDetails === null) {
                            instance.setContent('Loading...');
                            instance._error = null;
                        }

                    }
                });
            }

        },
    });


}(jQuery, window.commissionEngine.API_URL, Vue, swal, axios, window.location));