<template>
    <div 
            v-if="total > 0 && !isLoading"
            class="table-container">

        <div class="selection-control">
            <div class="field select-all is-pulled-left">
                <span>
                    <b-checkbox 
                            @click.native="selectAllOnPage()" 
                            :value="allOnPageSelected">{{ $i18n.get('label_select_all_taxonomies_page') }}</b-checkbox>
                </span>
            </div>
            <div class="field is-pulled-right">
                <b-dropdown
                        position="is-bottom-left"
                        v-if="taxonomies[0].current_user_can_edit"
                        :disabled="!isSelecting"
                        id="bulk-actions-dropdown">
                    <button
                            class="button is-white"
                            slot="trigger">
                        <span>{{ $i18n.get('label_bulk_actions') }}</span>
                        <b-icon icon="menu-down"/>
                    </button> 

                    <b-dropdown-item
                            id="item-delete-selected-items"
                            @click="deleteSelected()">
                        {{ $i18n.get('label_delete_selected_taxonomies') }}
                    </b-dropdown-item>
                    <b-dropdown-item disabled>{{ $i18n.get('label_edit_selected_taxonomies') + ' (Not ready)' }}
                    </b-dropdown-item>
                </b-dropdown>
            </div>
        </div>

        <div class="table-wrapper">
            <table class="tainacan-table">
                <thead>
                    <tr>
                        <!-- Checking list -->
                        <th>
                            &nbsp;
                            <!-- nothing to show on header -->
                        </th>
                        <!-- Name -->
                        <th>
                            <div class="th-wrap">{{ $i18n.get('label_name') }}</div>
                        </th>
                        <!-- Description -->
                        <th>
                            <div class="th-wrap">{{ $i18n.get('label_description') }}</div>
                        </th>
                        <!-- Actions -->
                        <th class="actions-header">
                            &nbsp;
                            <!-- nothing to show on header for actions cell-->
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr     
                            :class="{ 'selected-row': selected[index] }"
                            :key="index"
                            v-for="(taxonomy, index) of taxonomies">
                        <!-- Checking list -->
                        <td 
                                :class="{ 'is-selecting': isSelecting }"
                                class="checkbox-cell">
                            <b-checkbox 
                                    size="is-small"
                                    v-model="selected[index]"/> 
                        </td>
                        <!-- Name -->
                        <td 
                                class="column-default-width column-main-content"
                                @click="goToTaxonomyEditPage(taxonomy.id)"
                                :label="$i18n.get('label_name')" 
                                :aria-label="$i18n.get('label_name') + ': ' + taxonomy.name">
                            <p
                                    v-tooltip="{
                                        content: taxonomy.name,
                                        autoHide: false,
                                        placement: 'auto-start'
                                    }">
                                {{ taxonomy.name }}</p>
                        </td>
                        <!-- Description -->
                        <td
                                class="column-large-width" 
                                @click="goToTaxonomyEditPage(taxonomy.id)"
                                :label="$i18n.get('label_description')" 
                                :aria-label="$i18n.get('label_description') + ': ' + taxonomy.description">
                            <p
                                    v-tooltip="{
                                        content: taxonomy.description,
                                        autoHide: false,
                                        placement: 'auto-start'
                                    }">
                                {{ taxonomy.description }}</p>
                        </td>
                        <!-- Actions -->
                        <td 
                                @click="goToTaxonomyEditPage(taxonomy.id)"
                                class="actions-cell column-default-width" 
                                :label="$i18n.get('label_actions')">
                            <div class="actions-container">
                                <a 
                                        id="button-edit" 
                                        :aria-label="$i18n.getFrom('taxonomies','edit_item')" 
                                        @click="goToTaxonomyEditPage(taxonomy.id)">
                                    <b-icon 
                                            type="is-secondary" 
                                            icon="pencil"/>
                                </a>
                                <a 
                                        id="button-delete" 
                                        :aria-label="$i18n.get('label_button_delete')" 
                                        @click.prevent.stop="deleteOneTaxonomy(taxonomy.id)">
                                    <b-icon 
                                            type="is-secondary" 
                                            icon="delete"/>
                                </a>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script>
    import { mapActions } from 'vuex';
    import CustomDialog from '../other/custom-dialog.vue';

    export default {
        name: 'List',
        data() {
            return {
                selected: [],
                allOnPageSelected: false,
                isSelecting: false
            }
        },
        props: {
            isLoading: false,
            total: 0,
            page: 1,
            taxonomiesPerPage: 12,
            taxonomies: Array
        },
        watch: {
            taxonomies() {
                this.selected = [];
                for (let i = 0; i < this.taxonomies.length; i++)
                    this.selected.push(false);    
            },
            selected() {
                let allSelected = true;
                let isSelecting = false;
                for (let i = 0; i < this.selected.length; i++) {
                    if (this.selected[i] == false) {
                        allSelected = false;
                    } else {
                        isSelecting = true;
                    }
                }
                this.allOnPageSelected = allSelected;
                this.isSelecting = isSelecting;
            }
        },
        methods: {
            ...mapActions('taxonomy', [
                'deleteTaxonomy'
            ]),
            selectAllOnPage() {
                for (let i = 0; i < this.selected.length; i++) 
                    this.selected.splice(i, 1, !this.allOnPageSelected);
            },
            deleteOneTaxonomy(taxonomyId) {
                this.$modal.open({
                    parent: this,
                    component: CustomDialog,
                    props: {
                        icon: 'alert',
                        title: this.$i18n.get('label_warning'),
                        message: this.$i18n.get('info_warning_taxonomy_delete'),
                        onConfirm: () => {
                            this.deleteTaxonomy(taxonomyId)
                                .then(() => {
                                    // this.$toast.open({
                                    //     duration: 3000,
                                    //     message: this.$i18n.get('info_taxonomy_deleted'),
                                    //     position: 'is-bottom',
                                    //     type: 'is-secondary',
                                    //     queue: true
                                    // });
                                    for (let i = 0; i < this.selected.length; i++) {
                                        if (this.selected[i].id === this.taxonomyId)
                                            this.selected.splice(i, 1);
                                    }
                                })
                                .catch(() => {
                                    // this.$toast.open({
                                    //     duration: 3000,
                                    //     message: this.$i18n.get('info_error_deleting_taxonomy'),
                                    //     position: 'is-bottom',
                                    //     type: 'is-danger',
                                    //     queue: true
                                    // });
                                });
                        }
                    }
                });
            },
            deleteSelected() {
                this.$modal.open({
                    parent: this,
                    component: CustomDialog,
                    props: {
                        icon: 'alert',
                        title: this.$i18n.get('label_warning'),
                        message: this.$i18n.get('info_warning_selected_taxonomies_delete'),
                        onConfirm: () => {

                            for (let i = 0; i < this.taxonomies.length;  i++) {
                                if (this.selected[i]) {
                                    this.deleteTaxonomy(this.taxonomies[i].id)
                                        .then(() => {
                                            // this.load();
                                            // this.$toast.open({
                                            //     duration: 3000,
                                            //     message: this.$i18n.get('info_taxonomy_deleted'),
                                            //     position: 'is-bottom',
                                            //     type: 'is-secondary',
                                            //     queue: false
                                            // })
                                        }).catch(() => {
                                        // this.$toast.open({
                                        //     duration: 3000,
                                        //     message: this.$i18n.get('info_error_deleting_taxonomy'),
                                        //     position: 'is-bottom',
                                        //     type: 'is-danger',
                                        //     queue: false
                                        // });
                                    });
                                }
                            }
                            this.allOnPageSelected = false;
                        }
                    }
                });
            },
            goToTaxonomyPage(taxonomyId) {
                this.$router.push(this.$routerHelper.getTaxonomyPath(taxonomyId));
            },
            goToTaxonomyEditPage(taxonomyId) {
                this.$router.push(this.$routerHelper.getTaxonomyEditPath(taxonomyId));
            }
        }
    }
</script>

<style lang="scss" scoped>

    @import "../../scss/_variables.scss";

    .selection-control {
        
        padding: 6px 0px 0px 12px;
        background: white;
        height: 40px;

        .select-all {
            color: $gray-light;
            font-size: 14px;
            &:hover {
                color: $gray-light;
            }
        }
    }

</style>

