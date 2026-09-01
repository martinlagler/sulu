// @flow
import React from 'react';
import {render, screen, within} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {extendObservable as mockExtendObservable} from 'mobx';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import MultiSelectionStore from '../../../../stores/MultiSelectionStore';
import ResourceStore from '../../../../stores/ResourceStore';
import FormInspector from '../../FormInspector';
import ResourceFormStore from '../../stores/ResourceFormStore';
import GroupedSelection from '../../fields/GroupedSelection';

jest.mock('../../../../utils/Translator', () => ({
    translate: jest.fn((key, parameters) => parameters ? key + ':' + JSON.stringify(parameters) : key),
}));

jest.mock('../../../../stores/MultiSelectionStore', () => jest.fn(function() {
    mockExtendObservable(this, {items: [], loading: false});
    // eslint-disable-next-line testing-library/prefer-explicit-assert -- store method, not a query
    this.getById = jest.fn((id) => this.items.find((item) => item.id === id));
    this.set = jest.fn((items) => {
        this.items = items;
    });
    // A real reload flips "loading" on immediately (synchronously, before the fetch resolves) and
    // back off once it lands; this mock never resolves, so a test controls "landing" itself by
    // setting store.loading = false (and store.items, if it needs to).
    this.loadItems = jest.fn(() => {
        this.loading = true;
    });
}));

// the field only ever reads "formInspector.locale"; an automocked FormInspector is enough to provide it
jest.mock('../../../../stores/ResourceStore', () => jest.fn());
jest.mock('../../stores/ResourceFormStore', () => jest.fn());
jest.mock('../../FormInspector', () => jest.fn());

const mockOverlayItems = [
    {group: 'g1', groupName: 'General', id: 'a1', name: 'Size'},
    {group: 'g2', groupName: 'Marketing', id: 'a3', name: 'Season'},
];

// Stand in for the overlay: render its title as a button that confirms the fixture selection.
jest.mock('../../../MultiListOverlay', () => function MultiListOverlay(props) {
    const React = require('react');

    if (!props.open) {
        return null;
    }

    return React.createElement(
        'button',
        {onClick: () => props.onConfirm(mockOverlayItems), type: 'button'},
        props.title
    );
});

const ITEMS = [
    {group: 'g1', groupName: 'General', id: 'a1', name: 'Size'},
    {group: 'g1', groupName: 'General', id: 'a2', name: 'Fabric'},
    {group: 'g2', groupName: 'Marketing', id: 'a3', name: 'Season'},
];

const FIELD_TYPE_OPTIONS = {
    adapter: 'table',
    columns: [
        {name: 'required', title: 'app.required'},
        {name: 'variantSpecific', title: 'app.variant'},
    ],
    display_property: 'name',
    group_id_property: 'group',
    group_label_property: 'groupName',
    item_count_label: 'app.item_count',
    list_key: 'attributes',
    overlay_title: 'app.overlay_title',
    resource_key: 'attributes',
};

function renderField(props: Object = {}) {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'attributes'));

    const view = render(
        <GroupedSelection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={FIELD_TYPE_OPTIONS}
            formInspector={formInspector}
            {...props}
        />
    );

    // the mocked store is constructed by the field; fill it with the fixture items
    // $FlowFixMe
    const store = MultiSelectionStore.mock.instances[MultiSelectionStore.mock.instances.length - 1];
    store.items = props.items || ITEMS;

    return {...view, store, user: userEvent.setup()};
}

function getCard(title: string) {
    // $FlowFixMe
    return screen.getByText(title).closest('section');
}

test('Construct the store with a "fields" request parameter naming id, display, group id and group label', () => {
    renderField({value: [{id: 'a1', required: false, variantSpecific: false}]});

    // $FlowFixMe
    const [, , , , requestParameters] = MultiSelectionStore.mock.calls[MultiSelectionStore.mock.calls.length - 1];

    expect(requestParameters).toEqual({fields: 'id,name,group,groupName'});
});

test('Confirming the overlay should reload the store instead of trusting the overlay items', async() => {
    const {user, store} = renderField({value: [{id: 'a1', required: true, variantSpecific: false}]});

    await user.click(screen.getByText('sulu_admin.add'));
    await user.click(screen.getByText('app.overlay_title'));

    expect(store.loadItems).toHaveBeenCalledWith(['a1', 'a3']);
});

test('While the initial load is pending, the Loader renders and no cards do', () => {
    // Override the mock for exactly this render: start "loading" as if the very first fetch (the
    // one MultiSelectionStore's own constructor triggers) is still in flight, the way it really is
    // for a moment between mount and that fetch's resolution.
    // $FlowFixMe
    MultiSelectionStore.mockImplementationOnce(function() {
        mockExtendObservable(this, {items: [], loading: true});
        // eslint-disable-next-line testing-library/prefer-explicit-assert -- store method, not a query
        this.getById = jest.fn((id) => this.items.find((item) => item.id === id));
        this.set = jest.fn((items) => {
            this.items = items;
        });
        this.loadItems = jest.fn(() => {
            this.loading = true;
        });
    });

    const {container} = renderField({value: [{id: 'a1', required: false, variantSpecific: false}]});

    // $FlowFixMe
    expect(container.querySelector('.spinner')).toBeInTheDocument();
    expect(screen.queryByText('sulu_admin.add')).not.toBeInTheDocument();
});

test('Confirming the overlay does not show the Loader again; already-loaded cards stay on screen', async() => {
    const {container, user} = renderField({value: [{id: 'a1', required: false, variantSpecific: false}]});

    expect(screen.getByText('Size')).toBeInTheDocument();

    await user.click(screen.getByText('sulu_admin.add'));
    await user.click(screen.getByText('app.overlay_title'));

    // $FlowFixMe
    expect(container.querySelector('.spinner')).not.toBeInTheDocument();
    expect(screen.getByText('Size')).toBeInTheDocument();
});

test('Group the value into one card per group, in first-appearance order', () => {
    renderField({value: [
        {id: 'a3', required: false, variantSpecific: false},
        {id: 'a1', required: false, variantSpecific: false},
    ]});

    const titles = screen.getAllByText(/Marketing|General/).map((element) => element.textContent);

    expect(titles).toEqual(['Marketing', 'General']);
});

test('Render one column per configured entry with its translated title', () => {
    renderField({value: [{id: 'a1', required: false, variantSpecific: false}]});

    expect(screen.getByText('app.required')).toBeInTheDocument();
    expect(screen.getByText('app.variant')).toBeInTheDocument();
});

test('Render the display property as the row label', () => {
    renderField({value: [{id: 'a1', required: false, variantSpecific: false}]});

    expect(screen.getByText('Size')).toBeInTheDocument();
});

test('Render the item count in the card subtitle', () => {
    renderField({value: [
        {id: 'a1', required: false, variantSpecific: false},
        {id: 'a2', required: false, variantSpecific: false},
    ]});

    expect(screen.getByText('app.item_count:{"count":2}')).toBeInTheDocument();
});

test('Checking a column checkbox should change only that entry and that column', async() => {
    const changeSpy = jest.fn();
    const {user} = renderField({
        onChange: changeSpy,
        value: [
            {id: 'a1', required: false, variantSpecific: false},
            {id: 'a2', required: false, variantSpecific: false},
        ],
    });

    await user.click(within(screen.getByText('Size').closest('tr')).getAllByRole('checkbox')[0]);

    expect(changeSpy).toHaveBeenCalledWith([
        {id: 'a1', required: true, variantSpecific: false},
        {id: 'a2', required: false, variantSpecific: false},
    ]);
});

test('The row delete button should remove only that entry', async() => {
    const changeSpy = jest.fn();
    const {user} = renderField({
        onChange: changeSpy,
        value: [
            {id: 'a1', required: false, variantSpecific: false},
            {id: 'a2', required: false, variantSpecific: false},
        ],
    });

    await user.click(within(screen.getByText('Size').closest('tr')).getByLabelText('su-trash-alt'));

    expect(changeSpy).toHaveBeenCalledWith([{id: 'a2', required: false, variantSpecific: false}]);
});

test('The row delete button renders as the last cell of its row with an empty header cell', () => {
    renderField({value: [{id: 'a1', required: false, variantSpecific: false}]});

    // $FlowFixMe
    const row = screen.getByText('Size').closest('tr');
    // $FlowFixMe
    const cells = within(row).getAllByRole('cell');
    const removeCell = cells[cells.length - 1];
    expect(within(removeCell).getByLabelText('su-trash-alt')).toBeInTheDocument();
    expect(removeCell).toHaveClass('groupedSelectionRemoveCell');

    // $FlowFixMe
    const headerCells = screen.getAllByRole('columnheader');
    const removeHeaderCell = headerCells[headerCells.length - 1];
    expect(removeHeaderCell).toHaveTextContent('', {exact: true});
    expect(removeHeaderCell).toHaveClass('groupedSelectionHeaderCell');
    expect(removeHeaderCell).toHaveClass('groupedSelectionRemoveCell');
});

test('Every header cell renders with the field\'s own header cell class, not core\'s bold flat skin', () => {
    renderField({value: [{id: 'a1', required: false, variantSpecific: false}]});

    // $FlowFixMe
    const headerCells = screen.getAllByRole('columnheader');
    headerCells.forEach((headerCell) => {
        expect(headerCell).toHaveClass('groupedSelectionHeaderCell');
    });
});

test('The label and value columns carry the width classes that align the checkboxes across groups', () => {
    renderField({value: [{id: 'a1', required: false, variantSpecific: false}]});

    // $FlowFixMe
    const headerCells = screen.getAllByRole('columnheader');
    expect(headerCells[0]).toHaveClass('groupedSelectionLabelCell');
    expect(headerCells[1]).toHaveClass('groupedSelectionCell');
    expect(headerCells[2]).toHaveClass('groupedSelectionCell');

    // $FlowFixMe
    const row = screen.getByText('Size').closest('tr');
    // $FlowFixMe
    const cells = within(row).getAllByRole('cell');
    expect(cells[0]).toHaveClass('groupedSelectionLabelCell');
    expect(cells[1]).toHaveClass('groupedSelectionCell');
    expect(cells[2]).toHaveClass('groupedSelectionCell');
});

test('The row delete button does not use core\'s hover-only button cell class', () => {
    renderField({value: [{id: 'a1', required: false, variantSpecific: false}]});

    // $FlowFixMe
    const row = screen.getByText('Size').closest('tr');
    // $FlowFixMe
    const removeCell = within(row).getAllByRole('cell').pop();

    expect(removeCell).not.toHaveClass('buttonCell');
});

test('A disabled field renders no row delete button, no add button and no card delete action', () => {
    renderField({
        disabled: true,
        value: [{id: 'a1', required: false, variantSpecific: false}],
    });

    expect(screen.queryByLabelText('su-trash-alt')).not.toBeInTheDocument();
    expect(screen.queryByLabelText('sulu_admin.delete')).not.toBeInTheDocument();
    expect(screen.queryByText('sulu_admin.add')).not.toBeInTheDocument();
});

test('The first column header renders the configured "display_title"', () => {
    renderField({
        fieldTypeOptions: {...FIELD_TYPE_OPTIONS, display_title: 'app.attribute'},
        value: [{id: 'a1', required: false, variantSpecific: false}],
    });

    expect(screen.getByText('app.attribute')).toBeInTheDocument();
});

test('The first column header falls back to "sulu_admin.name" without a configured "display_title"', () => {
    renderField({value: [{id: 'a1', required: false, variantSpecific: false}]});

    expect(screen.getByText('sulu_admin.name')).toBeInTheDocument();
});

test('The card delete action should remove every entry of that group and no others', async() => {
    const changeSpy = jest.fn();
    const {user} = renderField({
        onChange: changeSpy,
        value: [
            {id: 'a1', required: false, variantSpecific: false},
            {id: 'a2', required: false, variantSpecific: false},
            {id: 'a3', required: false, variantSpecific: false},
        ],
    });

    await user.click(within(getCard('General')).getByLabelText('sulu_admin.delete'));

    expect(changeSpy).toHaveBeenCalledWith([{id: 'a3', required: false, variantSpecific: false}]);
});

test('Render only the add button when the value is empty', () => {
    renderField({value: []});

    expect(screen.getByText('sulu_admin.add')).toBeInTheDocument();
    expect(screen.queryByText('General')).not.toBeInTheDocument();
});

test('Skip a value entry whose item cannot be resolved', () => {
    renderField({value: [
        {id: 'a1', required: false, variantSpecific: false},
        {id: 'gone', required: false, variantSpecific: false},
    ]});

    expect(screen.getByText('Size')).toBeInTheDocument();
    expect(screen.getByText('app.item_count:{"count":1}')).toBeInTheDocument();
});

test('Confirming the overlay should append new entries with every column false and keep existing flags', async() => {
    const changeSpy = jest.fn();
    const {user} = renderField({
        onChange: changeSpy,
        value: [{id: 'a1', required: true, variantSpecific: false}],
    });

    await user.click(screen.getByText('sulu_admin.add'));
    await user.click(screen.getByText('app.overlay_title'));

    expect(changeSpy).toHaveBeenCalledWith([
        {id: 'a1', required: true, variantSpecific: false},
        {id: 'a3', required: false, variantSpecific: false},
    ]);
});

test('Render each checkbox reflecting its own entry flag', () => {
    renderField({value: [{id: 'a1', required: true, variantSpecific: false}]});

    // $FlowFixMe
    const checkboxes = within(screen.getByText('Size').closest('tr')).getAllByRole('checkbox');

    expect(checkboxes[0]).toBeChecked();
    expect(checkboxes[1]).not.toBeChecked();
});

test.each([
    'adapter',
    'columns',
    'display_property',
    'group_id_property',
    'group_label_property',
    'list_key',
    'overlay_title',
    'resource_key',
])('Throw when the "%s" option is missing', (option) => {
    const fieldTypeOptions = {...FIELD_TYPE_OPTIONS};
    delete fieldTypeOptions[option];
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'attributes'));

    expect(() => render(
        <GroupedSelection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            value={[]}
        />
    )).toThrow(option);
});
