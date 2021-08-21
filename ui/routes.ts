import Model from './pages/Model.vue';
import Server from './pages/Server.vue';

export default [
    { 
        name: 'home',
        path: '/',
        redirect: { name: 'model' },
    },
    {
        name: 'model',
        path: '/model',
        component: Model,
    },
    {
        name: 'server',
        path: '/server',
        component: Server,
    },
];
