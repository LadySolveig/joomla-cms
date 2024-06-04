describe('Test in backend that the module form', () => {
  beforeEach(() => {
    // Delete test data
    cy.task('queryDB', "DELETE FROM #__modules WHERE title = 'Test module'")
    cy.doAdministratorLogin();
    // Clear the filter
    cy.visit('/administrator/index.php?option=com_modules&filter=');
  });

  it('can create a module', () => {
    cy.visit('/administrator/index.php?option=com_modules&task=module.add&client_id=0&eid=44');
    cy.get('#jform_title').clear().type('Test module');
    cy.clickToolbarButton('Save & Close');

    cy.get('#system-message-container').contains('Module saved').should('exist');
    cy.contains('Test module');
  });

  it('can edit a module', () => {
    cy.db_createModule({ title: 'Test module', module: 'mod_custom' }).then((id) => {
      cy.visit(`/administrator/index.php?option=com_modules&task=module.edit&id=${id}`);
      cy.get('#jform_title').clear().type('Test module edited');
      cy.clickToolbarButton('Save & Close');

      cy.contains('Test module edited');
    });
  });
});
