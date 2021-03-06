<template>
    <div class="block">
        <span 
                v-if="isLoading"
                style="width: 100%"
                class="icon has-text-centered loading-icon">
            <div class="control has-icons-right is-loading is-clearfix" />
        </span>
        <div
                v-for="(option, index) in options.slice(0, filter.max_options)"
                :key="index"
                class="metadatum">
            <b-checkbox
                    v-if="index <= filter.max_options - 1"
                    v-model="selected"
                    :native-value="option.value">
                {{ option.label }}
            </b-checkbox>
            <div
                    class="view-all-button-container"
                    v-if="option.seeMoreLink && index == options.slice(0, filter.max_options).length - 1"
                    @click="openCheckboxModal()"
                    v-html="option.seeMoreLink"/>
        </div>
    </div>
</template>

<script>
    import { tainacan as axios } from '../../../js/axios/axios';
    import { filter_type_mixin } from '../filter-types-mixin';
    import CheckboxRadioModal from '../../../admin/components/other/checkbox-radio-modal.vue';

    export default {
        created(){

            this.collection = ( this.collection_id ) ? this.collection_id : this.filter.collection_id;
            this.metadatum = ( this.metadatum_id ) ? this.metadatum_id : this.filter.metadatum.metadatum_id;
            const vm = this;

            let in_route = '/collection/' + this.collection + '/metadata/' +  this.metadatum +'?nopaging=1';

            if(this.isRepositoryLevel || this.collection == 'filter_in_repository'){
                in_route = '/metadata?nopaging=1';
            }

            axios.get(in_route)
                .then( res => {
                    let result = res.data;
                    if( result && result.metadata_type ){
                        vm.metadatum_object = result;
                        vm.type = result.metadata_type;
                        vm.loadOptions();
                    }
                })
                .catch(error => {
                    this.$console.log(error);
                });

            this.$eventBusSearch.$on('removeFromFilterTag', this.cleanSearchFromTags);
        },
        props: {
            isRepositoryLevel: Boolean,
        },
        data(){
            return {
                isLoading: false,
                options: [],
                type: '',
                collection: '',
                metadatum: '',
                selected: [],
                metadatum_object: {}
            }
        },
        mixins: [filter_type_mixin],
        watch: {
            selected: function(){
                //this.selected = val;
                this.onSelect();
            }
        },
        methods: {
            loadOptions(){
                let promise = null;
                
                // Cancels previous Request
                if (this.getOptionsValuesCancel != undefined)
                    this.getOptionsValuesCancel.cancel('Facet search Canceled.');

                if ( this.type === 'Tainacan\\Metadata_Types\\Relationship' ) {
                    this.isLoading = true;
                    let collectionTarget = ( this.metadatum_object && this.metadatum_object.metadata_type_options.collection_id ) ?
                        this.metadatum_object.metadata_type_options.collection_id : this.collection_id;

                    promise = this.getValuesRelationship( collectionTarget, null, [], 0, this.filter.max_options, false, '1');
                    promise.request
                        .then(() => {
                            this.isLoading = false;
                            if(this.options.length > this.filter.max_options){
                                this.options.splice(this.filter.max_options);
                            }
                            this.selectedValues();
                        }).catch((error) => {
                            this.$console.error(error);
                    }) 
                } else {
                    this.isLoading = true;
                    promise = this.getValuesPlainText( this.metadatum, null, this.isRepositoryLevel, [], 0, this.filter.max_options, false, '1' );
                    promise.request
                        .then(() => {

                            this.isLoading = false;
                            if(this.options.length > this.filter.max_options){
                                this.options.splice(this.filter.max_options);
                            }
                            this.selectedValues();
                        }).catch((error) => {
                            this.$console.error(error);
                        });
                }

                // promise.request
                //     .then(() => {
                //         this.isLoading = false;
                        
                //     })
                //     .catch( error => {
                //         this.$console.log('error select', error );
                //         this.isLoading = false;
                //     });

                // Search Request Token for cancelling
                this.getOptionsValuesCancel = promise.source;

            },
            onSelect(){
                this.$emit('input', {
                    filter: 'checkbox',
                    compare: 'IN',
                    metadatum_id: this.metadatum,
                    collection_id: ( this.collection_id ) ? this.collection_id : this.filter.collection_id,
                    value: this.selected
                });

                let onlyLabels = [];

                if(!isNaN(this.selected[0])){
                    for (let aSelected of this.selected) {
                        let valueIndex = this.options.findIndex(option => option.value == aSelected);
                        
                        if (valueIndex >= 0) {
                            onlyLabels.push(this.options[valueIndex].label);
                        }
                    }
                }

                this.$eventBusSearch.$emit( 'sendValuesToTags', {
                    filterId: this.filter.id,
                    value: onlyLabels.length ? onlyLabels : this.selected,
                });
            },
            selectedValues(){
                if ( !this.query || !this.query.metaquery || !Array.isArray( this.query.metaquery ) )
                    return false;

                let index = this.query.metaquery.findIndex(newMetadatum => newMetadatum.key === this.metadatum );
                if ( index >= 0){
                    let query = this.query.metaquery.slice();
                    this.selected = query[ index ].value;
                } else {
                    this.selected = [];
                    return false;
                }
            },
            openCheckboxModal() {
                this.$modal.open({
                    parent: this,
                    component: CheckboxRadioModal,
                    props: {
                        //parent: parent,
                        filter: this.filter,
                        //taxonomy_id: this.taxonomy_id,
                        selected: this.selected,
                        metadatum_id: this.metadatum,
                        //taxonomy: this.taxonomy,
                        collection_id: this.collection,
                        metadatum_type: this.type,
                        metadatum_object: this.metadatum_object,
                        isRepositoryLevel: this.isRepositoryLevel,
                        query: this.query
                    },
                    events: {
                        appliedCheckBoxModal: () => this.loadOptions()
                    }
                });
            },
            cleanSearchFromTags(filterTag) {
                if (filterTag.filterId == this.filter.id) {

                    let selectedIndex = this.selected.findIndex(option => option == filterTag.singleValue);
                    let optionIndex = this.options.findIndex(option => option.label == filterTag.singleValue);
                    let alternativeIndex;

                    if (optionIndex >= 0) {
                        alternativeIndex = this.selected.findIndex(option => this.options[optionIndex].value == option);
                    }

                    if (selectedIndex >= 0 || alternativeIndex >= 0) {

                        selectedIndex >= 0 ? this.selected.splice(selectedIndex, 1) : this.selected.splice(alternativeIndex, 1); 

                        this.$emit('input', {
                            filter: 'checkbox',
                            compare: 'IN',
                            metadatum_id: this.metadatum,
                            collection_id: ( this.collection_id ) ? this.collection_id : this.filter.collection_id,
                            value: this.selected
                        });

                        this.$eventBusSearch.$emit( 'sendValuesToTags', {
                            filterId: this.filter.id,
                            value: this.selected
                        });

                        this.selectedValues();
                    }
                }
            }
        },
        beforeDestroy() {
            this.$eventBusSearch.$off('removeFromFilterTag', this.cleanSearchFromTags);
        }
    }
</script>

<style lang="scss" scoped>
    .view-all-button-container {
        display: flex;
        padding-left: 18px;
    }

    .is-loading:after {
        border: 2px solid white !important;
        border-top-color: #dbdbdb !important;
        border-right-color: #dbdbdb !important;
    }
</style>