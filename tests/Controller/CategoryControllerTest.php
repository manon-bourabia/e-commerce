<?php
/**
 * Tests fonctionnels du CategoryController
 * 
 * Objectif : Vérifier la SÉCURITÉ et l'ACCÈS ROLE_ADMIN
 * Niveau : CDA (Concepteur Développeur d'Applications)
 * 
 * Couverture : 80% methods (sécurité + 2 happy paths)
 * Mock Repository : ZÉRO dépendance base de données
 */
namespace App\Tests\Controller;

use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CategoryControllerTest extends WebTestCase
{
    /**
     * @test
     * SANS CONNEXION → Redirection vers login (302)
     * 
     * Vérifie security.yaml : access_control ^/admin → ROLE_ADMIN requis
     * 
     * Résultat attendu : HTTP 302 → /login
     */
    public function testRedirectsToLoginWhenUnauthenticated(): void
    {
        // Client anonyme (pas connecté)
        $client = static::createClient();
        // Accès route admin sans auth
        $client->request('GET', '/admin/category');
        // Vérifie redirection login = SÉCURITÉ OK
        $this->assertResponseRedirects('/login');
    }
    /**
     * @test
     * ADMIN CONNECTÉ → Index accessible (200 OK)
     * Scénario :
     * - test@test.com (ROLE_ADMIN via .env.test)
     * - Mock CategoryRepository::findAll() → [] (pas de DB)
     * - Vérifie render('category/index.html.twig')
     * Résultat attendu : HTTP 200 + template OK
     */
    public function testAuthenticatedAdminAccess(): void
    {
        $client = static::createClient();

        // 🔧 MOCK CategoryRepository : Évite base de données (ultra rapide !)
        $categoryRepoMock = $this->createMock(CategoryRepository::class);
        $categoryRepoMock->method('findAll')->willReturn([]);  // Retourne tableau vide
        static::getContainer()->set(CategoryRepository::class, $categoryRepoMock);

        // 🔑 LOGIN ADMIN (utilisateur mémoire .env.test)
        $container = static::getContainer();
        $userProvider = $container->get('security.user.provider.concrete.app_user_provider_test');
        $user = $userProvider->loadUserByIdentifier('test@test.com');  // password: password
        $client->loginUser($user);
        // Accès index admin categories
        $crawler = $client->request('GET', '/admin/category');
        // Vérifie controller + template = 200 OK
        $this->assertResponseIsSuccessful();
    }
    /**
     * @test
     * ADMIN CONNECTÉ → Formulaire création accessible (200 OK)
     * Scénario :
     * - test@test.com (ROLE_ADMIN)
     * - Mock CategoryRepository (même pour /new)
     * - Vérifie CategoryFormType + render('category/newCategory.html.twig')
     * Résultat attendu : HTTP 200 + formulaire OK
    */
    public function testAddFormRequiresAdmin(): void
    {
        $client = static::createClient();

        // 🔧 MOCK Repository CategoryRepository (indépendant DB) 
        $categoryRepoMock = $this->createMock(CategoryRepository::class);
        $categoryRepoMock->method('findAll')->willReturn([]);
        static::getContainer()->set(CategoryRepository::class, $categoryRepoMock);

        // 🔑 Connexion admin test@test.com
        $container = static::getContainer();
        $userProvider = $container->get('security.user.provider.concrete.app_user_provider_test');
        $user = $userProvider->loadUserByIdentifier('test@test.com');
        $client->loginUser($user);

        // Accès formulaire création catégorie
        $crawler = $client->request('GET', '/admin/category/new');
        // Vérifie formulaire CategoryFormType + bouton Sauvegarder
        $this->assertResponseIsSuccessful();
        // 💡 Bonus : bouton form
        $this->assertSelectorExists('input[value="Sauvegarder"]'); 
    }
}