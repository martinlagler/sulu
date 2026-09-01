// @flow
import React, {Fragment} from 'react';
import classNames from 'classnames';
import {action, computed, observable, reaction} from 'mobx';
import {observer} from 'mobx-react';
import {translate} from '../../../utils/Translator';
import Collapsible from '../../../components/Collapsible';
import CollapsibleCollection from '../../../components/CollapsibleCollection';
import Loader from '../../../components/Loader';
import Table from '../../../components/Table';
import MultiListOverlay from '../../MultiListOverlay';
import MultiSelectionStore from '../../../stores/MultiSelectionStore';
import groupedSelectionStyles from './groupedSelection.scss';
import GroupedSelectionCheckbox from './GroupedSelectionCheckbox';
import GroupedSelectionRemoveButton from './GroupedSelectionRemoveButton';
import type {FieldTypeProps} from '../../../types';

const REQUIRED_OPTIONS = [
    'adapter',
    'display_property',
    'group_id_property',
    'group_label_property',
    'list_key',
    'overlay_title',
    'resource_key',
];

type Entry = Object;
type Props = FieldTypeProps<?Array<Entry>>;

@observer
class GroupedSelection extends React.Component<Props> {
    selectionStore: MultiSelectionStore<string | number>;

    disposeInitialLoadReaction: () => void;

    @observable overlayOpen: boolean = false;

    // Only the *first* load blanks the field behind a `Loader` (render()); a reload triggered by
    // the overlay (handleOverlayConfirm) must leave the already-rendered cards in place instead.
    @observable initialLoadDone: boolean = false;

    constructor(props: Props) {
        super(props);

        const {fieldTypeOptions, formInspector} = this.props;

        REQUIRED_OPTIONS.forEach((option) => {
            if (!fieldTypeOptions[option]) {
                throw new Error('The "grouped_selection" field needs a "' + option + '" option to work properly');
            }
        });

        if (!fieldTypeOptions.columns || fieldTypeOptions.columns.length === 0) {
            throw new Error('The "grouped_selection" field needs at least one entry in the "columns" option');
        }

        const {
            display_property: displayProperty,
            group_id_property: groupIdProperty,
            group_label_property: groupLabelProperty,
            resource_key: resourceKey,
        } = fieldTypeOptions;

        this.selectionStore = new MultiSelectionStore(
            resourceKey,
            this.value.map((entry) => entry.id),
            formInspector.locale,
            'ids',
            {fields: ['id', displayProperty, groupIdProperty, groupLabelProperty].join(',')}
        );

        this.disposeInitialLoadReaction = reaction(
            () => this.selectionStore.loading,
            (loading) => {
                if (!loading) {
                    this.markInitialLoadDone();
                }
            },
            {fireImmediately: true}
        );
    }

    componentWillUnmount() {
        this.disposeInitialLoadReaction();
    }

    @action markInitialLoadDone = () => {
        this.initialLoadDone = true;
    };

    @computed get value(): Array<Entry> {
        return this.props.value || [];
    }

    @computed get columns(): Array<Object> {
        return this.props.fieldTypeOptions.columns;
    }

    @computed get displayTitle(): string {
        const {display_title: displayTitle} = this.props.fieldTypeOptions;

        return displayTitle || 'sulu_admin.name';
    }

    @computed get selectedItems(): Array<Object> {
        return this.value.map((entry) => this.selectionStore.getById(entry.id)).filter(Boolean);
    }

    @computed get groups(): Array<Object> {
        const {
            fieldTypeOptions: {
                group_id_property: groupIdProperty,
                group_label_property: groupLabelProperty,
            },
        } = this.props;

        const groups = [];
        const groupIndexes = {};

        this.value.forEach((entry) => {
            const item = this.selectionStore.getById(entry.id);

            if (!item) {
                return;
            }

            const groupId = item[groupIdProperty];

            if (!(groupId in groupIndexes)) {
                groupIndexes[groupId] = groups.length;
                groups.push({entries: [], id: groupId, label: item[groupLabelProperty] || ''});
            }

            groups[groupIndexes[groupId]].entries.push({entry, item});
        });

        return groups;
    }

    itemCountLabel = (count: number) => {
        const {item_count_label: itemCountLabel} = this.props.fieldTypeOptions;

        return translate(itemCountLabel || 'sulu_admin.grouped_selection_item_count', {count});
    };

    groupActions = (groupId: string | number): Array<Object> => {
        if (this.props.disabled) {
            return [];
        }

        return [{
            icon: 'su-trash-alt',
            label: translate('sulu_admin.delete'),
            onClick: () => this.handleGroupRemove(groupId),
        }];
    };

    @action handleAddClick = () => {
        this.overlayOpen = true;
    };

    @action handleOverlayClose = () => {
        this.overlayOpen = false;
    };

    @action handleOverlayConfirm = (selectedItems: Array<Object>) => {
        const {onChange, onFinish} = this.props;

        const existing = {};
        this.value.forEach((entry) => {
            existing[entry.id] = entry;
        });

        // The overlay's items lack the group properties (B1) — setting them would flash every row
        // into one untitled card, so reload from the resource instead of trusting them; already-
        // loaded rows keep rendering, removed rows drop out immediately (they leave `value` below),
        // and added rows appear, correctly grouped, once the refetch lands.
        this.selectionStore.loadItems(selectedItems.map((item) => item.id));
        this.overlayOpen = false;

        onChange(selectedItems.map((item) => existing[item.id] || this.emptyEntry(item.id)));
        onFinish();
    };

    handleColumnChange = (id: string | number, column: string, checked: boolean) => {
        const {onChange, onFinish} = this.props;

        onChange(this.value.map((entry) => entry.id === id ? {...entry, [column]: checked} : entry));
        onFinish();
    };

    handleEntryRemove = (id: string | number) => {
        const {onChange, onFinish} = this.props;

        onChange(this.value.filter((entry) => entry.id !== id));
        onFinish();
    };

    handleGroupRemove = (groupId: string | number) => {
        const {
            fieldTypeOptions: {group_id_property: groupIdProperty},
            onChange,
            onFinish,
        } = this.props;

        onChange(this.value.filter((entry) => {
            const item = this.selectionStore.getById(entry.id);

            return !item || item[groupIdProperty] !== groupId;
        }));
        onFinish();
    };

    emptyEntry(id: string | number): Entry {
        const entry = {id};

        this.columns.forEach((column) => {
            entry[column.name] = false;
        });

        return entry;
    }

    // `Table.Header`/`Table.Row` clone every child unconditionally (Header.js:66-68, Row.js:96-99),
    // so a conditionally-omitted cell must never appear as a `false`/`null` child — it is built as a
    // plain array instead, with the trailing delete cell pushed only when the field is not disabled.
    renderHeaderCells(): Array<Object> {
        const cells = [
            <Table.HeaderCell
                className={classNames(
                    groupedSelectionStyles.groupedSelectionHeaderCell,
                    groupedSelectionStyles.groupedSelectionLabelCell
                )}
                key="label"
            >
                {translate(this.displayTitle)}
            </Table.HeaderCell>,
            ...this.columns.map((column) => (
                <Table.HeaderCell
                    className={classNames(
                        groupedSelectionStyles.groupedSelectionHeaderCell,
                        groupedSelectionStyles.groupedSelectionCell
                    )}
                    key={column.name}
                >
                    {translate(column.title)}
                </Table.HeaderCell>
            )),
        ];

        if (!this.props.disabled) {
            cells.push(
                <Table.HeaderCell
                    className={classNames(
                        groupedSelectionStyles.groupedSelectionHeaderCell,
                        groupedSelectionStyles.groupedSelectionRemoveCell
                    )}
                    key="delete"
                />
            );
        }

        return cells;
    }

    renderRowCells(entry: Entry, item: Object): Array<Object> {
        const {disabled, fieldTypeOptions: {display_property: displayProperty}} = this.props;

        const cells = [
            <Table.Cell className={groupedSelectionStyles.groupedSelectionLabelCell} key="label">
                <span className={groupedSelectionStyles.groupedSelectionLabel}>
                    {item[displayProperty]}
                </span>
            </Table.Cell>,
            ...this.columns.map((column) => (
                <Table.Cell className={groupedSelectionStyles.groupedSelectionCell} key={column.name}>
                    <GroupedSelectionCheckbox
                        checked={!!entry[column.name]}
                        column={column.name}
                        disabled={!!disabled}
                        id={entry.id}
                        onChange={this.handleColumnChange}
                    />
                </Table.Cell>
            )),
        ];

        if (!disabled) {
            cells.push(<GroupedSelectionRemoveButton id={entry.id} key="delete" onClick={this.handleEntryRemove} />);
        }

        return cells;
    }

    renderGroup = (group: Object) => {
        return (
            <Collapsible
                actions={this.groupActions(group.id)}
                key={group.id}
                subtitle={this.itemCountLabel(group.entries.length)}
                title={group.label}
            >
                <div className={groupedSelectionStyles.groupedSelectionTable}>
                    <Table skin="flat">
                        <Table.Header>
                            {this.renderHeaderCells()}
                        </Table.Header>
                        <Table.Body>
                            {group.entries.map(({entry, item}) => (
                                <Table.Row id={entry.id} key={entry.id}>
                                    {this.renderRowCells(entry, item)}
                                </Table.Row>
                            ))}
                        </Table.Body>
                    </Table>
                </div>
            </Collapsible>
        );
    };

    render() {
        const {
            disabled,
            fieldTypeOptions: {adapter, list_key: listKey, overlay_title: overlayTitle, resource_key: resourceKey},
            formInspector,
        } = this.props;

        if (this.selectionStore.loading && !this.initialLoadDone) {
            return <Loader />;
        }

        return (
            <Fragment>
                <CollapsibleCollection onAddClick={disabled ? undefined : this.handleAddClick}>
                    {this.groups.map(this.renderGroup)}
                </CollapsibleCollection>
                <MultiListOverlay
                    adapter={adapter}
                    listKey={listKey}
                    locale={formInspector.locale}
                    onClose={this.handleOverlayClose}
                    onConfirm={this.handleOverlayConfirm}
                    open={this.overlayOpen}
                    preSelectedItems={this.selectedItems}
                    resourceKey={resourceKey}
                    title={translate(overlayTitle)}
                />
            </Fragment>
        );
    }
}

export default GroupedSelection;
