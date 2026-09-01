// @flow
import React from 'react';
import Checkbox from '../../../components/Checkbox';

type Props = {|
    checked: boolean,
    column: string,
    disabled: boolean,
    id: string | number,
    onChange: (id: string | number, column: string, checked: boolean) => void,
|};

export default class GroupedSelectionCheckbox extends React.PureComponent<Props> {
    static defaultProps = {
        checked: false,
        disabled: false,
    };

    handleChange = (checked: boolean) => {
        const {column, id, onChange} = this.props;

        onChange(id, column, checked);
    };

    render() {
        const {checked, disabled} = this.props;

        return <Checkbox checked={checked} disabled={disabled} onChange={this.handleChange} />;
    }
}
