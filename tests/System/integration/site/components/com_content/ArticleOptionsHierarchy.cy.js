describe('Test in frontend that the content article view honours the option hierarchy', () => {
  // The single article view resolves display options with the precedence
  // global < menu item < article. In addition a menu item option set to
  // 'use_article' defers to the article option and falls back to the global
  // value when the article is set to "Use Global".
  // We use show_author as a representative option: it renders the byline
  // (<dd class="createdby">Written by ...</dd>) only when it evaluates to true.

  const menuItemTitle = 'automated test article options';

  afterEach(() => {
    cy.db_deleteMenuItem({ title: menuItemTitle });
    cy.task('queryDB', "DELETE FROM #__content WHERE title = 'test article'");
    // Restore the global option to its default so tests do not leak state.
    cy.db_updateExtensionParameter('show_author', '1', 'com_content');
  });

  const assertBylineShown = () => cy.get('.createdby').should('contain', 'Test Author');
  const assertBylineHidden = () => cy.get('.createdby').should('not.exist');

  const createArticle = (attribs) => cy.db_createArticle({
    created_by_alias: 'Test Author',
    attribs,
  });

  const createMenuItem = (article, params) => cy.db_createMenuItem({
    title: menuItemTitle,
    alias: 'automated-test-article-options',
    link: `index.php?option=com_content&view=article&id=${article.id}`,
    path: 'automated-test-article-options',
    params,
  });

  describe('with a menu item whose option is set to use_article', () => {
    it('uses the article option over the global one (show)', () => {
      cy.db_updateExtensionParameter('show_author', '0', 'com_content')
        .then(() => createArticle('{"show_author":"1"}'))
        .then((article) => createMenuItem(article, '{"show_author":"use_article"}')
          .then((menuId) => {
            cy.visit(`/index.php?option=com_content&view=article&id=${article.id}&catid=${article.catid}&Itemid=${menuId}`);
            assertBylineShown();
            cy.checkForPhpNoticesOrWarnings();
          }));
    });

    it('uses the article option over the global one (hide)', () => {
      cy.db_updateExtensionParameter('show_author', '1', 'com_content')
        .then(() => createArticle('{"show_author":"0"}'))
        .then((article) => createMenuItem(article, '{"show_author":"use_article"}')
          .then((menuId) => {
            cy.visit(`/index.php?option=com_content&view=article&id=${article.id}&catid=${article.catid}&Itemid=${menuId}`);
            assertBylineHidden();
            cy.checkForPhpNoticesOrWarnings();
          }));
    });

    it('falls back to the global option when the article is set to use global', () => {
      cy.db_updateExtensionParameter('show_author', '1', 'com_content')
        .then(() => createArticle(''))
        .then((article) => createMenuItem(article, '{"show_author":"use_article"}')
          .then((menuId) => {
            cy.visit(`/index.php?option=com_content&view=article&id=${article.id}&catid=${article.catid}&Itemid=${menuId}`);
            assertBylineShown();
            cy.checkForPhpNoticesOrWarnings();
          }));
    });

    it('falls back to the global option (hide) when the article is set to use global', () => {
      cy.db_updateExtensionParameter('show_author', '0', 'com_content')
        .then(() => createArticle(''))
        .then((article) => createMenuItem(article, '{"show_author":"use_article"}')
          .then((menuId) => {
            cy.visit(`/index.php?option=com_content&view=article&id=${article.id}&catid=${article.catid}&Itemid=${menuId}`);
            assertBylineHidden();
            cy.checkForPhpNoticesOrWarnings();
          }));
    });

    it('lets an explicit article option override an explicit menu item option', () => {
      cy.db_updateExtensionParameter('show_author', '1', 'com_content')
        .then(() => createArticle('{"show_author":"1"}'))
        .then((article) => createMenuItem(article, '{"show_author":"0"}')
          .then((menuId) => {
            cy.visit(`/index.php?option=com_content&view=article&id=${article.id}&catid=${article.catid}&Itemid=${menuId}`);
            assertBylineShown();
            cy.checkForPhpNoticesOrWarnings();
          }));
    });
  });

  describe('with direct access and no menu item assigned', () => {
    it('applies the article option over the global one (hide)', () => {
      cy.db_updateExtensionParameter('show_author', '1', 'com_content')
        .then(() => createArticle('{"show_author":"0"}'))
        .then((article) => {
          cy.visit(`/index.php?option=com_content&view=article&id=${article.id}&catid=${article.catid}`);
          assertBylineHidden();
          cy.checkForPhpNoticesOrWarnings();
        });
    });

    it('applies the article option over the global one (show)', () => {
      cy.db_updateExtensionParameter('show_author', '0', 'com_content')
        .then(() => createArticle('{"show_author":"1"}'))
        .then((article) => {
          cy.visit(`/index.php?option=com_content&view=article&id=${article.id}&catid=${article.catid}`);
          assertBylineShown();
          cy.checkForPhpNoticesOrWarnings();
        });
    });

    it('honours the global option when the article is set to use global', () => {
      cy.db_updateExtensionParameter('show_author', '0', 'com_content')
        .then(() => createArticle(''))
        .then((article) => {
          cy.visit(`/index.php?option=com_content&view=article&id=${article.id}&catid=${article.catid}`);
          assertBylineHidden();
          cy.checkForPhpNoticesOrWarnings();
        });
    });
  });
});
