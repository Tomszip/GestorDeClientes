import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import { MainLayoutComponent } from './layout/main-layout/main-layout.component';
import { DashboardComponent } from './pages/dashboard/dashboard.component';
import { ConversacionesComponent } from './pages/conversaciones/conversaciones.component';
import { ClientesComponent } from './pages/clientes/clientes.component';
import { UsuariosComponent } from './pages/usuarios/usuarios.component';
import { ContenidosComponent } from './pages/contenidos/contenidos.component';
import { CategoriasComponent } from './pages/categorias/categorias.component';
import { ModeracionComponent } from './pages/moderacion/moderacion.component';
import { ConfiguracionComponent } from './pages/configuracion/configuracion.component';
import { LoginComponent } from './pages/login/login.component';
import { RegistroComponent } from './pages/registro/registro.component';
import { AuthGuard } from './guards/auth.guard';

const routes: Routes = [
  { path: 'login', component: LoginComponent },
  { path: 'registro', component: RegistroComponent },
  {
    path: '',
    component: MainLayoutComponent,
    canActivate: [AuthGuard],
    children: [
      { path: 'dashboard', component: DashboardComponent },
      { path: 'conversaciones', component: ConversacionesComponent },
      { path: 'clientes', component: ClientesComponent },
      { path: 'contenidos', component: ContenidosComponent },
      { path: 'usuarios', component: UsuariosComponent },
      { path: 'categorias', component: CategoriasComponent },
      { path: 'moderacion', component: ModeracionComponent },
      { path: 'configuracion', component: ConfiguracionComponent },
      { path: '', redirectTo: 'dashboard', pathMatch: 'full' }
    ]
  },
  { path: '**', redirectTo: 'login' }
];

@NgModule({
  imports: [RouterModule.forRoot(routes)],
  exports: [RouterModule]
})
export class AppRoutingModule {}
