<template>
    <table class="table is-bordered is-striped is-fullwidth">
        <tbody>
            <tr>
                <td>
                    <span class="has-tooltip-arrow has-tooltip-top has-tooltip-multiline" data-tooltip="The type of predictions the estimator makes.">
                        <span class="has-text-weight-medium">Estimator Type</span>
                    </span>
                </td>
                <td class="has-text-right">
                    <span>{{ model.type }}</span>
                </td>
            </tr>
            <tr>
                <td>
                    <span class="has-tooltip-arrow has-tooltip-top has-tooltip-multiline" data-tooltip="The data types the estimator is compatible with.">
                        <span class="has-text-weight-medium">Data Compatibility</span>
                    </span>
                </td>
                <td class="has-text-right">
                    <span>{{  }}</span>
                </td>
            </tr>
            <tr>
                <td>
                    <span class="has-tooltip-arrow has-tooltip-top has-tooltip-multiline" data-tooltip="The APIs that the estimator implements.">
                        <span class="has-text-weight-medium">Interfaces</span>
                    </span>
                </td>
                <td class="has-text-right">
                    <span>{{ }}</span>
                </td>
            </tr>
        </tbody>
    </table>
</template>

<script lang="ts">
import Vue from 'vue';
import gql from 'graphql-tag';

export const fragment = gql`
    fragment ModelInfo on Model {
        type
        compatibility
        interfaces {
            learner
            probabilistic
            scoring
        }
    }
`;

export default Vue.extend({
    props: {
        model: {
            type: Object,
            required: true,
        },
    },
});
</script>
