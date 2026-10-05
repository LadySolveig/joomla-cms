describe('Test that the command palette system plugin', () => {
  afterEach(() => cy.db_deleteMenuItem({ title: 'Automated test articles' }));

  it('can open the palette and find a menu item', () => {
    cy.doAdministratorLogin();
    cy.visit('/administrator/index.php');
    cy.intercept('GET', '**/administrator/index.php?option=com_ajax&group=system&plugin=commands&format=json&*').as('commands');
    cy.get('body').type('{ctrl}k');

    cy.wait('@commands');
    cy.get('#command_search').should('have.focus').type('Global Configuration');
    cy.get('#command-dialog-search-result [role="option"]').should('contain', 'Global Configuration');
  });

  it('can not load items without a token', () => {
    cy.doAdministratorLogin();
    cy.request('/administrator/index.php?option=com_ajax&group=system&plugin=commands&format=json&mode=load')
      .then((response) => {
        expect(response.body.success).to.eq(false);
        expect(response.body.message).to.eq('The most recent request was denied because it had an invalid security token. Please refresh the page and try again.');
      });
  });

  it('can not load items as a guest', () => {
    cy.request('/administrator/index.php?option=com_ajax&group=system&plugin=commands&format=json&mode=load')
      .then((response) => {
        expect(response.body.success).to.eq(false);
        expect(response.body.message).to.eq('You don\'t have permission to access this. Please contact a website administrator if this is incorrect.');
      });
  });

  it('can not load items in the frontend', () => {
    cy.visit('/index.php?option=com_users&view=login');
    cy.window()
      .then((win) => win.Joomla.getOptions('csrf.token'))
      .then((token) => cy.request(`/index.php?option=com_ajax&group=system&plugin=commands&format=json&mode=load&${token}=1`))
      .then((response) => {
        expect(response.body.success).to.eq(false);
        expect(response.body.message).to.eq('Access Denied');
      });
  });

  it('can not list items without permission', () => {
    cy.db_createUser({
      name: 'automated test manager', username: 'automatedtestmanager', email: 'manager@example.com', group_id: 6,
    })
      .then(() => {
        cy.doAdministratorLogin('automatedtestmanager', 'test', false);
        cy.window()
          .then((win) => win.Joomla.getOptions('csrf.token'))
          .then((token) => cy.request(`/administrator/index.php?option=com_ajax&group=system&plugin=commands&format=json&mode=load&${token}=1`))
          .then((response) => {
            expect(response.body.success).to.eq(true);
            expect(response.body.data.items.map((item) => item.title)).to.include('Articles').and.not.include('Global Configuration');
          });
      });
  });

  it('can list a custom menu of a menu module the user can see', () => {
    cy.db_createMenuType({ title: 'automated test menu', menutype: 'automated-test-menu', client_id: 1 })
      .then(() => cy.db_createMenuItem({
        title: 'Automated test articles',
        alias: 'automated-test-articles',
        path: 'automated-test-articles',
        menutype: 'automated-test-menu',
        client_id: 1,
        link: 'index.php?option=com_content&view=articles',
      }))
      .then(() => cy.db_createModule({
        title: 'automated test menu', module: 'mod_menu', position: 'menu', client_id: 1, access: 1, params: '{"menutype":"automated-test-menu"}',
      }))
      .then(() => cy.db_createUser({
        name: 'automated test manager', username: 'automatedtestmanager', email: 'manager@example.com', group_id: 6,
      }))
      .then(() => {
        cy.doAdministratorLogin('automatedtestmanager', 'test', false);
        cy.window()
          .then((win) => win.Joomla.getOptions('csrf.token'))
          .then((token) => cy.request(`/administrator/index.php?option=com_ajax&group=system&plugin=commands&format=json&mode=load&${token}=1`))
          .then((response) => {
            expect(response.body.data.items.map((item) => item.title)).to.include('Automated test articles');
          });
      });
  });

  it('can not list a custom menu of a menu module the user can not see', () => {
    cy.db_createMenuType({ title: 'automated test menu', menutype: 'automated-test-menu', client_id: 1 })
      .then(() => cy.db_createMenuItem({
        title: 'Automated test articles',
        alias: 'automated-test-articles',
        path: 'automated-test-articles',
        menutype: 'automated-test-menu',
        client_id: 1,
        link: 'index.php?option=com_content&view=articles',
      }))
      // Access level 6 is "Super Users"
      .then(() => cy.db_createModule({
        title: 'automated test menu', module: 'mod_menu', position: 'menu', client_id: 1, access: 6, params: '{"menutype":"automated-test-menu"}',
      }))
      .then(() => cy.db_createUser({
        name: 'automated test manager', username: 'automatedtestmanager', email: 'manager@example.com', group_id: 6,
      }))
      .then(() => {
        cy.doAdministratorLogin('automatedtestmanager', 'test', false);
        cy.window()
          .then((win) => win.Joomla.getOptions('csrf.token'))
          .then((token) => cy.request(`/administrator/index.php?option=com_ajax&group=system&plugin=commands&format=json&mode=load&${token}=1`))
          .then((response) => {
            expect(response.body.data.items.map((item) => item.title)).to.not.include('Automated test articles');
          });
      });
  });
});
