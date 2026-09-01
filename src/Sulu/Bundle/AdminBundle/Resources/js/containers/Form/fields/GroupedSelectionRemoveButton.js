// @flow
import React from 'react';
import Icon from '../../../components/Icon';
import Table from '../../../components/Table';
import groupedSelectionStyles from './groupedSelection.scss';

type Props = {|
    id: string | number,
    onClick: (id: string | number) => void,
|};

export default class GroupedSelectionRemoveButton extends React.PureComponent<Props> {
    handleClick = () => {
        const {id, onClick} = this.props;

        onClick(id);
    };

    render() {
        return (
            <Table.Cell className={groupedSelectionStyles.groupedSelectionRemoveCell}>
                <button onClick={this.handleClick} type="button">
                    <Icon name="su-trash-alt" />
                </button>
            </Table.Cell>
        );
    }
}
