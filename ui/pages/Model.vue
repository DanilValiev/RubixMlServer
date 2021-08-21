<template>
    <div>
        <section class="section">
            <div class="container">
                <div class="columns">
                    <div class="column is-half">

                    </div>
                    <div class="column is-half">
                        <h2 class="title is-size-4"><span class="icon mr-3"><i class="fas fa-sliders-h"></i></span>Hyper-parameters</h2>
                        <hyper-parameters v-if="model" :model="model"></hyper-parameters>
                    </div>
                </div>
            </div>
        </section>
        <section class="section">
            <div class="container">
                <inference-level v-if="model" :model="model"></inference-level>
                <inference-rate-chart v-if="model" :model="model"></inference-rate-chart>
            </div>
        </section>
        <page-loader :loading="loading"></page-loader>
    </div>
</template>

<script lang="ts">
import Vue from 'vue';
import { fragment as HyperparametersFragment } from '../components/Hyperparameters.vue';
import { fragment as InferenceLevelFragment } from '../components/InferenceLevel.vue';
import { fragment as InferenceRateChartFragment } from '../components/InferenceRateChart.vue';
import gql from 'graphql-tag';
import bus from '../providers/bus';

export default Vue.extend({
    data() {
        return {
            model: null,
            stream: null,
            loading: false,
        };
    },
    mounted() : void {
        this.loading = true;

        this.$apollo.query({
            query: gql`
                query getModelPage {
                    model {
                        ...Hyperparameters
                        ...InferenceLevel
                        ...InferenceRateChart
                    }
                }
                ${HyperparametersFragment}
                ${InferenceLevelFragment}
                ${InferenceRateChartFragment}
            `,
        }).then((response) => {
            this.model = response.data.model;

            this.$sse('/model/events', { format: 'json' }).then((stream) => {
                stream.subscribe('request-received', (event) => {
                    this.server.httpStats.transfers.received += event.size;
                });

                stream.subscribe('dataset-inferred', (event) => {
                    this.model.numSamplesInferred += event.numSamples;
                });

                this.stream = stream;

                this.loading = false;
            });
        }).catch((error) => {
            bus.$emit('communication-error', {
                error,
            });
        });
    },
    beforeDestroy() : void {
        if (this.stream) {
            this.stream.close();
        }
    },
});
</script>
