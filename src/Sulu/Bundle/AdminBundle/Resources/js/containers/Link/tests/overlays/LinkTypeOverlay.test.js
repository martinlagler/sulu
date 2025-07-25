// @flow
import React from 'react';
import {mount, shallow} from 'enzyme';
import LinkTypeOverlay from '../../overlays/LinkTypeOverlay';

jest.mock('../../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../SingleListOverlay', () => jest.fn(function() {
    return <div>single list overlay</div>;
}));

test('Render overlay with minimal config', () => {
    const linkOverlay = mount(
        <LinkTypeOverlay
            href={undefined}
            onCancel={jest.fn()}
            onConfirm={jest.fn()}
            onHrefChange={jest.fn()}
            open={true}
            options={
                {
                    displayProperties: ['title'],
                    emptyText: 'No page selected',
                    icon: 'su-document',
                    listAdapter: 'column_list',
                    overlayTitle: 'Choose page',
                    resourceKey: 'pages',
                    targets: {
                        '_blank': 'sulu_admin.link_blank',
                        '_self': 'sulu_admin.link_self',
                        '_parent': 'sulu_admin.link_parent',
                        '_top': 'sulu_admin.link_top',
                    },
                }
            }
        />
    );

    expect(linkOverlay.find('Form').render()).toMatchSnapshot();
});

test('Render overlay without options', () => {
    expect(() => shallow(
        <LinkTypeOverlay
            href={undefined}
            onCancel={jest.fn()}
            onConfirm={jest.fn()}
            onHrefChange={jest.fn()}
            open={true}
            options={undefined}
        />
    )).toThrow('The LinkTypeOverlay needs some options in order to work!');
});

test('Render overlay with query enabled', () => {
    const linkOverlay = mount(
        <LinkTypeOverlay
            href={undefined}
            onCancel={jest.fn()}
            onConfirm={jest.fn()}
            onHrefChange={jest.fn()}
            onQueryChange={jest.fn()}
            open={true}
            options={
                {
                    displayProperties: ['title'],
                    emptyText: 'No page selected',
                    icon: 'su-document',
                    listAdapter: 'column_list',
                    overlayTitle: 'Choose page',
                    resourceKey: 'pages',
                    targets: {
                        '_blank': 'sulu_admin.link_blank',
                        '_self': 'sulu_admin.link_self',
                        '_parent': 'sulu_admin.link_parent',
                        '_top': 'sulu_admin.link_top',
                    },
                }
            }
        />
    );

    expect(linkOverlay.find('Form').render()).toMatchSnapshot();
});

test('Render overlay with anchor enabled', () => {
    const linkOverlay = mount(
        <LinkTypeOverlay
            href={undefined}
            onAnchorChange={jest.fn()}
            onCancel={jest.fn()}
            onConfirm={jest.fn()}
            onHrefChange={jest.fn()}
            open={true}
            options={
                {
                    displayProperties: ['title'],
                    emptyText: 'No page selected',
                    icon: 'su-document',
                    listAdapter: 'column_list',
                    overlayTitle: 'Choose page',
                    resourceKey: 'pages',
                    targets: {
                        '_blank': 'sulu_admin.link_blank',
                        '_self': 'sulu_admin.link_self',
                        '_parent': 'sulu_admin.link_parent',
                        '_top': 'sulu_admin.link_top',
                    },
                }
            }
        />
    );

    expect(linkOverlay.find('Form').render()).toMatchSnapshot();
});

test('Render overlay with target enabled', () => {
    const linkOverlay = mount(
        <LinkTypeOverlay
            href={undefined}
            onCancel={jest.fn()}
            onConfirm={jest.fn()}
            onHrefChange={jest.fn()}
            onTargetChange={jest.fn()}
            open={true}
            options={
                {
                    displayProperties: ['title'],
                    emptyText: 'No page selected',
                    icon: 'su-document',
                    listAdapter: 'column_list',
                    overlayTitle: 'Choose page',
                    resourceKey: 'pages',
                    targets: {
                        '_blank': 'sulu_admin.link_blank',
                        '_self': 'sulu_admin.link_self',
                        '_parent': 'sulu_admin.link_parent',
                        '_top': 'sulu_admin.link_top',
                    },
                }
            }
        />
    );

    expect(linkOverlay.find('Form').render()).toMatchSnapshot();
});

test('Render overlay with title enabled', () => {
    const linkOverlay = mount(
        <LinkTypeOverlay
            href={undefined}
            onCancel={jest.fn()}
            onConfirm={jest.fn()}
            onHrefChange={jest.fn()}
            onTitleChange={jest.fn()}
            open={true}
            options={
                {
                    displayProperties: ['title'],
                    emptyText: 'No page selected',
                    icon: 'su-document',
                    listAdapter: 'column_list',
                    overlayTitle: 'Choose page',
                    resourceKey: 'pages',
                    targets: {
                        '_blank': 'sulu_admin.link_blank',
                        '_self': 'sulu_admin.link_self',
                        '_parent': 'sulu_admin.link_parent',
                        '_top': 'sulu_admin.link_top',
                    },
                }
            }
        />
    );

    expect(linkOverlay.find('Form').render()).toMatchSnapshot();
});
