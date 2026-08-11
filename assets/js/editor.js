(function (blocks, blockEditor, components, element) {

const registerBlockType = blocks.registerBlockType;

const {
    TextControl,
    CheckboxControl,
    Spinner
} = components;

const {
    Fragment,
    createElement,
    useState,
    useEffect
} = element;

const {
    BlockControls,
    useBlockProps
} = blockEditor;

registerBlockType('dwz-verein-list/dwz-list', {

    edit: function (props) {

        const { attributes, setAttributes } = props;

        const {
            vkz,
            apiToken,
            showStatus,
            showNation,
            linkNationToFide,
            linkEloToFide,
            linkRapidToFide,
            linkBlitzToFide,
            showLastUpdate,
            showTitle,
            showElo,
            showRapid,
            showBlitz,
            showIndex
        } = attributes;

        const blockProps = useBlockProps({
            className: 'wp-block-dwz-verein-list-editor'
        });

        return createElement(
            Fragment,
            null,

            createElement(
                BlockControls,
                null
            ),

            createElement(
                'div',
                Object.assign({}, blockProps, {
                    style: {
                        padding: '24px',
                        border: '2px solid #0073aa',
                        borderRadius: '4px',
                        backgroundColor: '#f8f9fa'
                    }
                }),

                createElement(
                    'h3',
                    {
                        style: {
                            marginTop: 0,
                            marginBottom: '20px',
                            color: '#0073aa'
                        }
                    },
                    'DWZ-Liste'
                ),

                createElement(
                    'p',
                    {
                        style: {
                            marginTop: 0,
                            marginBottom: '20px',
                            color: '#000000'
                        }
                    },
                    'Die VKZ (Vereinskennziffer) und der API-Token (Zugangsschlüssel) sind erforderlich, um die DWZ-Liste des Vereins anzuzeigen. Der API-Token kann hier sehr einfach generiert werden: ',
                    createElement(
                        'a',
                        {
                            href: 'https://www.schachbund.de/wertungsportal-api.html',
                            target: '_blank',
                            rel: 'noopener noreferrer'
                        },
                        'https://www.schachbund.de/wertungsportal-api.html'
                    )
                ),
                createElement(
                    TextControl,
                    {
                        label: 'VKZ (Vereinskennziffer)',
                        value: vkz || '',
                        onChange: function (value) {
                            setAttributes({
                                vkz: value
                            });
                        },
                        placeholder: 'VKZ eingeben',
                        style: {
                            marginBottom: '5px'
                        }
                    }
                ),

                createElement(
                    TextControl,
                    {
                        label: 'API-Token (Zugangsschlüssel)',
                        value: apiToken || '',
                        onChange: function (value) {
                            setAttributes({
                                apiToken: value
                            });
                        },
                        placeholder: 'API-Token eingeben',
                        style: {
                            marginBottom: '20px'
                        }
                    }
                ),

                createElement(
                    CheckboxControl,
                    {
                        label:
                            'Status anzeigen (Passive Mitglieder werden mit "P" markiert)',
                        checked: showStatus,

                        onChange: function (value) {
                            setAttributes({
                                showStatus: value
                            });
                        }
                    }
                ),

                createElement(
                    CheckboxControl,
                    {
                        label:
                            'DWZ-Index anzeigen',
                        checked: showIndex,

                        onChange: function (value) {
                            setAttributes({
                                showIndex: value
                            });
                        }
                    }
                ),

                createElement(
                    CheckboxControl,
                    {
                        label:
                            'Woche der letzten DWZ-Auswertung anzeigen',
                        checked: showLastUpdate,

                        onChange: function (value) {
                            setAttributes({
                                showLastUpdate: value
                            });
                        }
                    }
                ),

                createElement(
                    CheckboxControl,
                    {
                        label:
                            'Standard-Elo anzeigen',
                        checked: showElo,
                        onChange: function (value) {
                            setAttributes({
                                showElo: value
                            });
                        }
                    }
                ),

                showElo &&
                    createElement(
                        'div',
                        {
                            style: {
                                marginLeft: '24px',
                                marginTop: '4px',
                                marginBottom: '8px'
                            }
                        },
                        createElement(
                            CheckboxControl,
                            {
                                label: 'Link zum FIDE-Profil setzen',
                                checked: !!linkEloToFide,
                                onChange: function (value) {
                                    setAttributes({
                                        linkEloToFide: value
                                    });
                                }
                            }
                        )
                    ),

                createElement(
                    CheckboxControl,
                    {
                        label: 'Rapid-Elo anzeigen',
                        checked: showRapid,
                        onChange: function (value) {
                            setAttributes({
                                showRapid: value
                            });
                        }
                    }
                ),

                showRapid &&
                    createElement(
                        'div',
                        {
                            style: {
                                marginLeft: '24px',
                                marginTop: '4px',
                                marginBottom: '8px'
                            }
                        },
                        createElement(
                            CheckboxControl,
                            {
                                label: 'Link zum FIDE-Profil setzen',
                                checked: !!linkRapidToFide,
                                onChange: function (value) {
                                    setAttributes({
                                        linkRapidToFide: value
                                    });
                                }
                            }
                        )
                    ),

                createElement(
                    CheckboxControl,
                    {
                        label: 'Blitz-Elo anzeigen',
                        checked: showBlitz,
                        onChange: function (value) {
                            setAttributes({
                                showBlitz: value
                            });
                        }
                    }
                ),

                showBlitz &&
                    createElement(
                        'div',
                        {
                            style: {
                                marginLeft: '24px',
                                marginTop: '4px',
                                marginBottom: '8px'
                            }
                        },
                        createElement(
                            CheckboxControl,
                            {
                                label: 'Link zum FIDE-Profil setzen',
                                checked: !!linkBlitzToFide,
                                onChange: function (value) {
                                    setAttributes({
                                        linkBlitzToFide: value
                                    });
                                }
                            }
                        )
                    ),

                createElement(
                    CheckboxControl,
                    {
                        label: 'FIDE-Nation anzeigen',
                        checked: showNation,
                        onChange: function (value) {
                            setAttributes({
                                showNation: value
                            });
                        }
                    }
                ),

                showNation &&
                    createElement(
                        'div',
                        {
                            style: {
                                marginLeft: '24px',
                                marginTop: '4px',
                                marginBottom: '8px'
                            }
                        },
                        createElement(
                            CheckboxControl,
                            {
                                label: 'Link zum FIDE-Profil setzen',
                                checked: !!linkNationToFide,
                                onChange: function (value) {
                                    setAttributes({
                                        linkNationToFide: value
                                    });
                                }
                            }
                        )
                    ),
                
                
                    createElement(
                        CheckboxControl,
                        {
                            label: 'FIDE-Titel anzeigen',
                            checked: showTitle,

                            onChange: function (value) {
                                setAttributes({
                                    showTitle: value
                                });
                            }
                        }
                    ),
                createElement(
                    'div',
                    {
                        style: {
                            marginTop: '20px',
                            paddingTop: '20px',
                            borderTop: '1px solid #ddd'
                        }
                    },

                    !vkz
                        ? createElement(
                              'p',
                              {
                                  style: {
                                      color: '#999'
                                  }
                              },
                              '👆 Bitte einen Verein auswählen'
                          )
                        : createElement(
                              'p',
                              {
                                  style: {
                                      color: '#28a745',
                                      fontWeight: 'bold'
                                  }
                              },
                              '✓ Die DWZ-Liste wird nun angezeigt.'
                          )
                )
            )
        );
    },

    save: function () {
        return null;
    }
});

})(
wp.blocks,
wp.blockEditor,
wp.components,
wp.element
);
