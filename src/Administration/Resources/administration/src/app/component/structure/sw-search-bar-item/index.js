import template from './sw-search-bar-item.html.twig';
import './sw-search-bar-item.scss';

/**
 * @public
 * @description
 * Renders the search result items based on the item type.
 * @status ready
 * @example-type code-only
 * @component-example
 * <sw-search-bar-item :item="{ type: 'customer', entity: [{ name: 'customer name', id: 'uuid' }]}">
 * </sw-search-bar-item>
 */
export default {
    name: 'sw-search-bar-item',
    template,

    props: {
        item: {
            type: Object,
            required: false,
            default: () => ({})
        },
        type: {
            required: true,
            type: String
        },
        index: {
            type: Number,
            required: true
        },
        column: {
            type: Number,
            required: true
        },
        searchTerm: {
            type: String,
            required: false,
            default: null
        }
    },

    computed: {
        componentClasses() {
            return [
                {
                    'is--active': this.isActive
                }
            ];
        }
    },

    data() {
        return {
            isActive: false,
            searchTypes: null
        };
    },

    created() {
        this.createdComponent();
    },

    destroyed() {
        this.destroyedComponent();
    },

    methods: {
        createdComponent() {
            this.registerEvents();

            if (this.index === 0 && this.column === 0) {
                this.isActive = true;
            }
        },

        destroyedComponent() {
            this.removeEvents();
        },

        registerEvents() {
            this.$parent.$on('sw-search-bar-active-item-index', this.checkActiveState);
            this.$parent.$on('sw-search-bar-on-keyup-enter', this.onEnter);
        },

        removeEvents() {
            this.$parent.$off('sw-search-bar-active-item-index', this.checkActiveState);
            this.$parent.$off('sw-search-bar-on-keyup-enter', this.onEnter);
        },

        checkActiveState({ index, column }) {
            if (index === this.index && column === this.column) {
                this.isActive = true;
                return;
            }

            if (this.isActive) {
                this.isActive = false;
            }
        },

        onEnter(index, column) {
            if (index !== this.index || column !== this.column) {
                return;
            }

            const routerLink = this.$refs.routerLink;
            this.$router.push(routerLink.to);
        },

        onMouseEnter(originalDomEvent) {
            this.$parent.$emit('sw-search-bar-item-mouse-over', {
                originalDomEvent,
                index: this.index,
                column: this.column
            });
            this.isActive = true;
        }
    }
};
