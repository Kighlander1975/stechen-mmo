import { createApp } from 'vue';
import SiteHeader from './components/layout/SiteHeader.vue';
import AppStatus from './components/AppStatus.vue';

const components = {
    'site-header': SiteHeader,
    'app-status': AppStatus,
};

function parseProps(element) {
    const rawProps = element.dataset.props || '{}';

    try {
        return JSON.parse(rawProps);
    } catch (error) {
        console.error('Invalid Vue island props:', error, element);
        return {};
    }
}

export function mountVueIslands() {
    document.querySelectorAll('[data-vue-component]').forEach((element) => {
        if (element.dataset.vueMounted === 'true') {
            return;
        }

        const componentName = element.dataset.vueComponent;
        const component = components[componentName];

        if (!component) {
            console.warn(`Vue island component "${componentName}" is not registered.`, element);
            return;
        }

        const props = parseProps(element);

        createApp(component, props).mount(element);
        element.dataset.vueMounted = 'true';
    });
}
