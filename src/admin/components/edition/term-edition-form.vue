<template>
    <form 
            id="termEditForm" 
            class="tainacan-form" 
            @submit.prevent="saveEdition(editForm)">    
        
        <b-field 
                :addons="false"
                :type="formErrors['name'] != undefined ? 'is-danger' : ''" 
                :message="formErrors['name'] != undefined ? formErrors['name'] : ''"> 
            <label class="label">
                {{ $i18n.get('label_name') }} 
                <span 
                        class="required-term-asterisk" 
                        :class="formErrors['name'] != undefined ? 'is-danger' : ''">*</span> 
                <help-button 
                        :title="$i18n.getHelperTitle('terms', 'name')" 
                        :message="$i18n.getHelperMessage('terms', 'name')"/>
            </label>
            <b-input 
                    v-model="editForm.name" 
                    name="name" 
                    @focus="clearErrors('name')"/>
        </b-field>

        <b-field
                :addons="false"
                :type="formErrors['description'] != undefined ? 'is-danger' : ''" 
                :message="formErrors['description'] != undefined ? formErrors['description'] : ''">
            <label class="label">
                {{ $i18n.get('label_description') }}
                <help-button 
                        :title="$i18n.getHelperTitle('terms', 'description')" 
                        :message="$i18n.getHelperMessage('terms', 'description')"/>
            </label>
            <b-input 
                    type="textarea" 
                    name="description" 
                    v-model="editForm.description" 
                    @focus="clearErrors('description')" />
        </b-field>
       
       <b-field 
                :addons="false"
                :type="formErrors['parent_term'] != undefined ? 'is-danger' : ''" 
                :message="formErrors['parent_term'] != undefined ? formErrors['parent_term'] : ''">
            <label class="label">
                {{ $i18n.get('label_parent_term') }} 
                <help-button 
                        :title="$i18n.getHelperTitle('terms', 'parent_term')" 
                        :message="$i18n.getHelperMessage('terms', 'parent_term')"/>
            </label>
            <b-select
                id="parent_term_select"
                v-model="editForm.parent"
                :placeholder="$i18n.get('instruction_select_a_parent_term')">
                <option
                        @focus="clearErrors('label_parent_term')"
                        id="tainacan-select-parent-term"
                        v-for="(parentTerm, index) in parentTermsList"
                        :key="index"
                        :value="editForm.parent.id">
                    {{ parentTerm.name }}
                </option>
            </b-select>
        </b-field>

        <div class="field is-grouped form-submit">  
            <div class="control">
                <button 
                        class="button is-outlined" 
                        @click.prevent="cancelEdition()" 
                        slot="trigger">
                    {{ $i18n.get('cancel') }}
                </button>
            </div>
            <div class="control">
                <button 
                        class="button is-success" 
                        type="submit">
                    {{ $i18n.get('save') }}
                </button>
            </div>
        </div>
        <p class="help is-danger">{{ formErrorMessage }}</p>
    </form>
</template>

<script>
import { mapActions, mapGetters } from 'vuex';

export default {
    name: 'TermEditionForm',
    data(){
        return {
            editForm: {},
            oldForm: {},
            formErrors: {},
            formErrorMessage: '',
            closedByForm: false
        }
    }, 
    props: {
        index: '',
        editedTerm: Object,
        originalTerm: Object, 
        categoryId: ''
    },
    computed: {
        parentTermsList() {
            return this.getTerms();
        }
    },
    created() {
        
        this.editForm = this.editedTerm;
        this.formErrors = this.editForm.formErrors != undefined ? this.editForm.formErrors : {};
        this.formErrorMessage = this.editForm.formErrors != undefined ? this.editForm.formErrorMessage : ''; 
        
        this.oldForm = JSON.parse(JSON.stringify(this.originalTerm));

    },
    beforeDestroy() {
        if (this.closedByForm) {
            this.editedTerm.saved = true;
        } else {
            this.oldForm.saved = this.editForm.saved;
            if (JSON.stringify(this.editForm) != JSON.stringify(this.oldForm)) 
                this.editedTerm.saved = false;
            else    
                this.editedTerm.saved = true;
        }
    },
    methods: {
        ...mapActions('category', [
            'sendTerm',
            'updateTerm'
        ]),
        ...mapGetters('category', [
            'getTerms'
        ]),
        saveEdition(term) {

            if (term.term_id == 'new') {
                this.sendTerm({
                    categoryId: this.categoryId, 
                    index: this.index, 
                    name: this.editForm.name,
                    description: this.editForm.description,
                    parent: this.editForm.parent
                    })
                    .then(() => {
                        this.editForm = {};
                        this.formErrors = {};
                        this.formErrorMessage = '';
                        this.closedByForm = true;
                        this.$emit('onEditionFinished');
                    })
                    .catch((error) => {
                        // for (let error of errors.errors) {     
                        //     for (let attribute of Object.keys(error))
                        //         this.formErrors[attribute] = error[attribute];
                        // }
                        // this.formErrorMessage = errors.error_message;
                        this.$emit('onErrorFound');
                        this.$console.log(error);
                        // this.editForm.formErrors = this.formErrors;
                        // this.editForm.formErrorMessage = this.formErrorMessage;
                    });

            } else {
  
                this.updateTerm({categoryId: this.categoryId, termId: term.term_id, index: this.index, options: this.editForm})
                    .then(() => {
                        this.editForm = {};
                        this.formErrors = {};
                        this.formErrorMessage = '';
                        this.closedByForm = true;
                        this.$emit('onEditionFinished');
                    })
                    .catch((error) => {
                        // for (let error of errors.errors) {     
                        //     for (let attribute of Object.keys(error))
                        //         this.formErrors[attribute] = error[attribute];
                        // }
                        // this.formErrorMessage = errors.error_message;
                        this.$emit('onErrorFound');
                        this.$console.log(error);
                        // this.editForm.formErrors = this.formErrors;
                        // this.editForm.formErrorMessage = this.formErrorMessage;
                    });
            }
        },
        clearErrors(attribute) {
            this.formErrors[attribute] = undefined;
        },
        cancelEdition() {
            this.closedByForm = true;
            this.$emit('onEditionCanceled');
        },
    }
}
</script>

<style lang="scss" scoped>

    @import "../../scss/_variables.scss";
 
    form {
        padding: 1.0em 2.0em;
        border-top: 1px solid $draggable-border-color;
        border-bottom: 1px solid $draggable-border-color;
        margin-top: 1.0em;
    }

</style>

