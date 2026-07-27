import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import ListDataTable from '@/Components/ListDataTable.vue';

const maskProcessNumberMock = vi.fn();

vi.mock('@/Composables/useMask', () => ({
    useMask: () => ({
        maskProcessNumber: maskProcessNumberMock,
    }),
}));

const globalStubs = {
    'v-card': { template: '<div><slot /></div>' },
    'v-checkbox': { template: '<input type="checkbox" />' },
    'v-chip': { template: '<span><slot /></span>' },
    'v-btn': { template: '<button><slot /></button>' },
    'v-icon': { template: '<i />' },
};

function createWrapper(props = {}) {
    return mount(ListDataTable, {
        props: {
            items: [{ id: 1, name: 'Item 1' }],
            reference: (item) => ({ label: 'Referência', value: item.name }),
            data: [],
            ...props,
        },
        global: {
            stubs: globalStubs,
        },
    });
}

function getDataValueEl(wrapper) {
    return wrapper.find('.truncate.font-weight-bold.leading-tight');
}

describe('ListDataTable.vue - resolveDataValue', () => {
    beforeEach(() => {
        maskProcessNumberMock.mockReset();
    });

    it('renders the value returned by a function-based dataItem.value', () => {
        const wrapper = createWrapper({
            data: [{ label: 'Fase', value: (item) => `Fase de ${item.name}` }],
        });

        expect(getDataValueEl(wrapper).text()).toBe('Fase de Item 1');
    });

    it('renders a static (non-function) dataItem.value', () => {
        const wrapper = createWrapper({
            data: [{ label: 'Status', value: 'Aprovado' }],
        });

        expect(getDataValueEl(wrapper).text()).toBe('Aprovado');
    });

    it('renders the fallback dash when the resolved value is null', () => {
        const wrapper = createWrapper({
            data: [{ label: 'Status', value: null }],
        });

        expect(getDataValueEl(wrapper).text()).toBe('-');
    });

    it('renders the fallback dash when the resolved value is undefined', () => {
        const wrapper = createWrapper({
            data: [{ label: 'Status', value: (item) => item.missingProp }],
        });

        expect(getDataValueEl(wrapper).text()).toBe('-');
    });

    it('preserves falsy-but-defined values instead of using the fallback dash', () => {
        const wrapperEmptyString = createWrapper({
            data: [{ label: 'Status', value: '' }],
        });
        expect(getDataValueEl(wrapperEmptyString).text()).toBe('');

        const wrapperZero = createWrapper({
            data: [{ label: 'Status', value: 0 }],
        });
        expect(getDataValueEl(wrapperZero).text()).toBe('0');
    });

    it('does not call maskProcessNumber for labels other than "Número do processo"', () => {
        createWrapper({
            data: [{ label: 'Status', value: 'Aprovado' }],
        });

        expect(maskProcessNumberMock).not.toHaveBeenCalled();
    });

    it('applies maskProcessNumber to the resolved value when the label is "Número do processo"', () => {
        maskProcessNumberMock.mockReturnValue('12345.678901/2345-67');

        const wrapper = createWrapper({
            data: [{ label: 'Número do processo', value: '1234567890123456712' }],
        });

        expect(maskProcessNumberMock).toHaveBeenCalledWith('1234567890123456712');
        expect(getDataValueEl(wrapper).text()).toBe('12345.678901/2345-67');
    });

    it('falls back to the raw value when maskProcessNumber returns a falsy result', () => {
        maskProcessNumberMock.mockReturnValue('');

        const wrapper = createWrapper({
            data: [{ label: 'Número do processo', value: 'abc' }],
        });

        expect(getDataValueEl(wrapper).text()).toBe('abc');
    });

    it('calls maskProcessNumber with the fallback dash when the process number value is missing', () => {
        maskProcessNumberMock.mockReturnValue('');

        const wrapper = createWrapper({
            data: [{ label: 'Número do processo', value: undefined }],
        });

        expect(maskProcessNumberMock).toHaveBeenCalledWith('-');
        expect(getDataValueEl(wrapper).text()).toBe('-');
    });

    it('resolves the value via the function form before applying the process number mask', () => {
        maskProcessNumberMock.mockReturnValue('12345.678901/2345-67');

        const wrapper = createWrapper({
            data: [{ label: 'Número do processo', value: (item) => `${item.id}234567890123456712` }],
        });

        expect(maskProcessNumberMock).toHaveBeenCalledWith('1234567890123456712');
        expect(getDataValueEl(wrapper).text()).toBe('12345.678901/2345-67');
    });

    it('sets the title attribute to the stringified resolved value', () => {
        const wrapper = createWrapper({
            data: [{ label: 'Status', value: 123 }],
        });

        expect(getDataValueEl(wrapper).attributes('title')).toBe('123');
    });
});