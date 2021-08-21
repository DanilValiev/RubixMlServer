<template>
    <table class="table is-bordered is-striped is-fullwidth">
        <tbody>
            <tr v-for="(hyperparameter, offset) in model.hyperparameters" :key="offset">
                <td>
                    <span class="has-text-weight-medium has-text-first-letter-capitalized">{{ hyperparameter.name }}</span>
                </td>
                <td class="has-text-right">
                    <span>{{ hyperparameter.value }}</span>
                </td>
            </tr>
        </tbody>
    </table>
</template>

<script lang="ts">
import Vue from 'vue';
import gql from 'graphql-tag';

export const fragment = gql`
    fragment Hyperparameters on Model {
        hyperparameters {
            name
            value
        }
    }
`;

export default Vue.extend({
    props: {
        model: {
            type: Object,
            required: true,
        },
    }
});
</script>
