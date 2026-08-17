import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { BehaviorSubject, Observable, tap } from 'rxjs';
import { environment } from '../../environments/environment';
import { AuthService } from './auth.service';
import { ConfiguracionPublica, Tema } from '../models/crm.models';

const NOMBRE_SITIO_POR_DEFECTO = 'CRM Pro';

@Injectable({ providedIn: 'root' })
export class ConfiguracionService {
  private apiUrl = environment.apiUrl;

  // Estado compartido: AppComponent la carga una sola vez al arrancar,
  // y cualquier otro componente (sidebar, login, registro) se suscribe
  // para enterarse del nombre del sitio sin volver a pedirlo.
  private configuracionSubject = new BehaviorSubject<ConfiguracionPublica | null>(null);
  configuracion$ = this.configuracionSubject.asObservable();

  constructor(private http: HttpClient, private auth: AuthService) {}

  cargar(): Observable<ConfiguracionPublica> {
    return this.http.get<ConfiguracionPublica>(`${this.apiUrl}/config/obtener_configuracion.php`).pipe(
      tap(config => this.configuracionSubject.next(config))
    );
  }

  getNombreSitio(): string {
    return this.configuracionSubject.value?.nombreSitio || NOMBRE_SITIO_POR_DEFECTO;
  }

  listarTemas(): Observable<Tema[]> {
    return this.http.get<Tema[]>(`${this.apiUrl}/config/listar_temas.php`, {
      headers: this.auth.getHeaders(),
    });
  }

  actualizar(nombreSitio: string, temaActivoId: number): Observable<any> {
    return this.http.post<any>(`${this.apiUrl}/config/actualizar_configuracion.php`, { nombreSitio, temaActivoId }, {
      headers: this.auth.getHeaders(),
    }).pipe(
      tap(() => this.cargar().subscribe())
    );
  }
}
