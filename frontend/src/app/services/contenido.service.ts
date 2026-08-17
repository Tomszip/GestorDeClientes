import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';
import { AuthService } from './auth.service';
import { Contenido, ContenidoCompartido, ContenidoModeracion } from '../models/crm.models';

@Injectable({ providedIn: 'root' })
export class ContenidoService {
  private apiUrl = environment.apiUrl;

  constructor(private http: HttpClient, private auth: AuthService) {}

  listar(): Observable<Contenido[]> {
    return this.http.get<Contenido[]>(`${this.apiUrl}/contenidos/listar.php`, {
      headers: this.auth.getHeaders(),
    });
  }

  crear(formData: FormData): Observable<Contenido> {
    return this.http.post<Contenido>(`${this.apiUrl}/contenidos/crear.php`, formData, {
      headers: this.auth.getHeaders(),
    });
  }

  eliminar(id: number): Observable<any> {
    return this.http.post<any>(`${this.apiUrl}/contenidos/eliminar.php`, { id }, {
      headers: this.auth.getHeaders(),
    });
  }

  compartidosConmigo(): Observable<ContenidoCompartido[]> {
    return this.http.get<ContenidoCompartido[]>(`${this.apiUrl}/contenidos/compartidos_conmigo.php`, {
      headers: this.auth.getHeaders(),
    });
  }

  compartir(contenidoId: number, usuarioIds: number[]): Observable<any> {
    return this.http.post<any>(`${this.apiUrl}/contenidos/compartir.php`, { contenidoId, usuarioIds }, {
      headers: this.auth.getHeaders(),
    });
  }

  // Solo admin (el backend valida el permiso igual, esto es la pantalla de Moderación).
  listarTodos(): Observable<ContenidoModeracion[]> {
    return this.http.get<ContenidoModeracion[]>(`${this.apiUrl}/contenidos/listar_todos.php`, {
      headers: this.auth.getHeaders(),
    });
  }

  cambiarEstado(id: number, estado: 'visible' | 'oculto'): Observable<any> {
    return this.http.post<any>(`${this.apiUrl}/contenidos/cambiar_estado.php`, { id, estado }, {
      headers: this.auth.getHeaders(),
    });
  }

  urlArchivo(rutaArchivo: string): string {
    return `${this.apiUrl}/uploads/${rutaArchivo}`;
  }
}
