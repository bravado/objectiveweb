define(['Boiler', './settings', 'path!../../../../index.php', './directoryMenu/component', '../../../libs/metaproject/components/grid/component', './entryDetails/component'],
    function (Boiler, settings, root, DirectoryMenuComponent, GridComponent, EntryDetailsComponent) {

        var DirectoryEntry = metaproject.Model({
            id:null,
            oid:null,
            namespace:null,
            identifier:null,
            displayName:null,
            profileURL:null,
            photoURL:null,
            password: null
        });

        var Module = function (globalContext) {

            var context = new Boiler.Context(globalContext);
            context.addSettings(settings);

            var datasource = new metaproject.DataSource(root + "/directory", { model:DirectoryEntry }),
                nav = new datasource.Nav({});

            context.listen('Directory.get', function(ev) {
                datasource.get(ev.id, ev.callback);
            });

            context.listen('Directory.save', function(ev) {
                datasource.save(ev.model, ev.callback)
                context.notify('Directory.updated');
            });

            context.listen('Directory.updated', function() {
                nav.reload();
            });



            //scoped DomController that will be effective only on $('#page-content')
            var controller = new Boiler.DomController($('#page-content'));
            //add routes with DOM node selector queries and relevant components
            controller.addRoutes({
                ".main-menu" : new DirectoryMenuComponent(context)
            });
            controller.start();

            var controller = new Boiler.UrlController($(".appcontent"));
            controller.addRoutes({
                'directory':new GridComponent(nav, {
                    cols:{
                        'ID':'id',
                        'Namespace':'namespace',
                        'Identifier':'identifier',
                        'Name':'displayName'
                    },
                    actions:[
                        {
                            label:'Edit',
                            fn:function (model) { // TODO receber o elemento da row (caso delete)
                                Boiler.UrlController.goTo("directory/" + ko.utils.unwrapObservable(model.id));
                            },
                            css:{
                                'btn-mini':true,
                                'btn-success':true
                            }
                        }

                    ]
                }),
                'directory/{id}': new EntryDetailsComponent(context)
            });
            controller.start();

        };
        return Module;
    });