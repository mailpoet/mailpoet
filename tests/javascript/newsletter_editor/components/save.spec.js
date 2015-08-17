describe('Save', function() {
    describe('save method', function() {
        it('triggers beforeEditorSave event', function() {
            var spy = sinon.spy();
            global.stubChannel({
                trigger: spy,
            });
            global.mailpoet_post_wpi = sinon.stub();
            EditorApplication.toJSON = sinon.stub();
            EditorApplication.module("components.save").save();
            expect(spy.withArgs('beforeEditorSave').calledOnce).to.be.true;
        });

        it('triggers afterEditorSave event', function() {
            var stub = sinon.stub().callsArgWith(2, { success: true }),
                spy = sinon.spy();
            global.mailpoet_post_wpi = stub;
            global.stubChannel({
                trigger: spy,
            });
            EditorApplication.toJSON = sinon.stub();
            EditorApplication.module("components.save").save();
            expect(spy.withArgs('afterEditorSave').calledOnce).to.be.true;
        });

        it('sends newsletter json to server for saving', function() {
            var mock = sinon.mock({ mailpoet_post_wpi: function() {} }).expects('mailpoet_post_wpi').once();
            global.stubChannel();
            global.mailpoet_post_wpi = mock;

            EditorApplication.toJSON = sinon.stub().returns({});
            EditorApplication.module("components.save").save();

            mock.verify();
        });
    });

    describe('view', function() {
        var view;
        before(function() {
            EditorApplication._contentContainer = { isValid: sinon.stub().returns(true) };
            global.stubConfig();
            view = new (EditorApplication.module('components.save').SaveView)();
        });

        it('renders', function() {
            expect(view.render).to.not.throw();
        });

        describe('once rendered', function() {
            var view;
            beforeEach(function() {
                EditorApplication._contentContainer = { isValid: sinon.stub().returns(true) };
                view = new (EditorApplication.module('components.save').SaveView)();
                view.render();
            });

            it('triggers newsletter saving when clicked on save button', function() {
                var mock = sinon.mock({ trigger: function() {} }).expects('trigger').once().withArgs('save');
                global.stubChannel({
                    trigger: mock,
                });
                view.$('.mailpoet_save_button').click();

                mock.verify();
            });

            it('displays saving options when clicked on save options button', function() {
                view.$('.mailpoet_save_show_options').click();
                expect(view.$('.mailpoet_save_options')).to.not.have.$class('mailpoet_hidden');
            });
        });
    });
});
