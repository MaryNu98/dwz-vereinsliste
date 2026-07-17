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
            showStatus,
            showNation,
            showTitle,
            showElo,
            showRapid,
            showBlitz,
            showIndex
        } = attributes;

        const blockProps = useBlockProps({
            className: 'wp-block-dwz-verein-list-editor'
        });

        const [clubs, setClubs] = useState([]);
        const [search, setSearch] = useState('');
        const [loading, setLoading] = useState(true);
        const [error, setError] = useState(false);

        useEffect(() => {

            const url =
                window.location.origin +
                '/wp-json/dwz/v1/clubs';

            fetch(url)
                .then((response) => {

                    if (!response.ok) {
                        throw new Error(
                            'HTTP Fehler: ' + response.status
                        );
                    }

                    return response.json();
                })
                .then((data) => {

                    const mappedClubs = (data.data || []).map(
                        (club) => ({
                            vkz: String(club.clubVkz),
                            name: club.clubName
                        })
                    );

                    setClubs(mappedClubs);
                    setLoading(false);
                })
                .catch((err) => {

                    console.error(
                        'Fehler beim Laden der Vereine:',
                        err
                    );

                    setError(true);
                    setLoading(false);
                });

        }, []);

        const filteredClubs =
            search.length < 2
                ? []
                : clubs
                      .filter((club) =>
                          (
                              club.name +
                              ' ' +
                              club.vkz
                          )
                              .toLowerCase()
                              .includes(search.toLowerCase())
                      )
                      .slice(0, 25);

        const selectedClub = clubs.find(
            (club) => String(club.vkz) === String(vkz)
        );

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
                    TextControl,
                    {
                        label: 'Verein suchen',
                        value: search,
                        onChange: setSearch,
                        placeholder: 'Vereinsname oder VKZ eingeben...'
                    }
                ),

                loading &&
                    createElement(
                        'div',
                        {
                            style: {
                                marginBottom: '15px'
                            }
                        },
                        createElement(Spinner)
                    ),

                error &&
                    createElement(
                        'p',
                        {
                            style: {
                                color: 'red',
                                fontWeight: 'bold'
                            }
                        },
                        'Fehler beim Laden der Vereinsliste.'
                    ),

                !loading &&
                filteredClubs.length > 0 &&
                    createElement(
                        'div',
                        {
                            style: {
                                maxHeight: '300px',
                                overflowY: 'auto',
                                backgroundColor: '#fff',
                                border: '1px solid #ddd',
                                borderRadius: '4px',
                                marginBottom: '15px'
                            }
                        },

                        filteredClubs.map((club) =>
                            createElement(
                                'div',
                                {
                                    key: club.vkz,

                                    onClick: function () {

                                        setSearch(
                                            club.name +
                                            ' (' +
                                            club.vkz +
                                            ')'
                                        );

                                        setAttributes({
                                            vkz: club.vkz
                                        });
                                    },

                                    style: {
                                        padding: '10px',
                                        cursor: 'pointer',
                                        borderBottom:
                                            '1px solid #eee'
                                    }
                                },

                                club.name +
                                ' (' +
                                club.vkz +
                                ')'
                            )
                        )
                    ),

                selectedClub &&
                    createElement(
                        'div',
                        {
                            style: {
                                marginBottom: '20px',
                                padding: '12px',
                                backgroundColor: '#e7f5ff',
                                border: '1px solid #74c0fc',
                                borderRadius: '4px'
                            }
                        },

                        createElement(
                            'strong',
                            null,
                            'Ausgewählter Verein'
                        ),

                        createElement(
                            'div',
                            null,
                            selectedClub.name
                        ),

                        createElement(
                            'div',
                            null,
                            'VKZ: ',
                            selectedClub.vkz
                        )
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
                            'Elo anzeigen (Dazu müssen in den Plugin-Einstellungen die FIDE-Daten importiert werden)',
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

                showElo &&
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

                showElo &&
                    createElement(
                        CheckboxControl,
                        {
                            label: 'FIDE-Nation anzeigen (mit Link zum FIDE-Profil)',
                            checked: showNation,
                            onChange: function (value) {
                                setAttributes({
                                    showNation: value
                                });
                            }
                        }
                    ),
                
                showElo &&
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
